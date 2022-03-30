<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Router;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;

class ArgumentParser implements ArgumentParserInterface
{
    private const TYPE_MAP = [
        'int' => '/^-?\d+$/',
        'integer' => '/^-?\d+$/i',
        'float' => '/^(([-+])?[.,]\b(\d+)(?:[Ee]([+-])?(\d+)?)?\b)|(?:([+-])?\b(\d+)(?:[.,]?(\d+))?(?:[Ee]([+-])?(\d+)?)?\b)$/',
        'double' => '/^(([-+])?[.,]\b(\d+)(?:[Ee]([+-])?(\d+)?)?\b)|(?:([+-])?\b(\d+)(?:[.,]?(\d+))?(?:[Ee]([+-])?(\d+)?)?\b)$/',
        'bool' => '/^(?:true|yes|1|on|off|0|no|false)$/',
    ];

    public function parse(array &$arguments, array $parameters = []): array
    {
        $result = [];

        foreach ($parameters as $parameter) {
            $aliases = array_map('trim', explode('|', $parameter['name']));
            foreach ($aliases as $alias) {
                $i = array_search($alias, $arguments);
                if ($i === false) {
                    continue;
                }

                $argument = $arguments[$i];
                if (stripos($argument, "{$alias}=") === 0) {
                    [$p, $value] = explode('=', $argument, 2);
                    $result[$aliases[0]] = $value;
                    unset($arguments[$i]);
                    continue;
                }

                if ($argument === $alias) {
                    $value = true;
                    $type = $parameter['type'] ?? '';
                    unset($arguments[$i]);

                    if (stripos($arguments[$i + 1] ?? '-', '-') !== 0 && $type !== 'bool') {
                        $value = $arguments[$i + 1];
                        unset($arguments[$i + 1]);
                    }

                    $result[trim($aliases[0], '-')] = $value;
                    continue;
                }
            }
        }

        foreach ($parameters as $meta) {
            $parameter = trim(explode('|', $meta['name'])[0]);
            if (($meta['required'] ?? false) && !isset($result[$parameter])) {
                throw new \BadFunctionCallException(
                    "Missing required parameter '{$parameter}'"
                );
            }

            if (isset($meta['default']) && !isset($result[$parameter])) {
                $result[$parameter] = $meta['default'];

                continue;
            }

            if (!isset($meta['type'], $result[$parameter]) || preg_match(static::TYPE_MAP[$meta['type']], $result[$parameter]) !== false) {
                continue;
            }

            if (!filter_var($result[$parameter], static::TYPE_MAP[$meta['type']])) {
                throw new \InvalidArgumentException(
                    "The value '{$result[$parameter]}' for {$parameter} must be of type {$meta['type']}"
                );
            }
        }

        $arguments = array_values($arguments);

        return $result;
    }
}
