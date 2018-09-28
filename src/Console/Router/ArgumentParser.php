<?php declare(strict_types=1);

namespace Onion\Console\Router;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;

class ArgumentParser implements ArgumentParserInterface
{
    public function parse(array &$arguments, array $parameters = []): array
    {
        $result = [];

        foreach (array_keys($parameters) as $parameter) {
            $aliases = array_map('trim', explode('|', $parameter));
            foreach ($aliases as $alias) {
                $i = array_search($alias, $arguments);
                if ($i === false) {
                    continue;
                }

                $argument = $arguments[$i];
                if (stripos($argument, "{$alias}=") === 0) {
                    [$p, $value] = explode('=', $argument, 2);
                    $result[$aliases[0]] = $value;
                    unset($arguments[$index]);
                    continue;
                }

                if ($argument === $alias) {
                    $value = true;
                    $type = $parameters[$alias]['type'] ?? '';
                    unset($arguments[$i]);

                    if (stripos($arguments[$i+1] ?? '-', '-') !== 0 && $type !== 'bool') {
                        $value = $arguments[$i+1];
                        unset($arguments[$i+1]);
                    }

                    $result[$aliases[0]] = $value;
                    continue;
                }
            }
        }

        foreach ($parameters as $parameter => $meta) {
            $parameter = trim(explode('|', $parameter)[0]);
            if (($meta['required'] ?? false) && !isset($result[$parameter])) {
                throw new \BadFunctionCallException(
                    "Missing required parameter '{$parameter}'"
                );
            }

            if (!isset($meta['type'], $result[$parameter])) {
                continue;
            }

            $checkFunction = "is_{$meta['type']}";
            if (function_exists($checkFunction) && !$checkFunction($result[$parameter])) {
                throw new \InvalidArgumentException(
                    "The value '{$result[$parameter]}' for {$parameter} must be of type {$meta['type']}"
                );
            }
        }

        $arguments = array_values($arguments);

        return $result;
    }
}
