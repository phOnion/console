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
        'string' => '/^(.*)$/',
    ];

    public function parse(array &$arguments, array $parameters = []): array
    {
        $result = [];

        foreach ($parameters as $parameter) {
            $aliases = array_map('trim', explode('|', $parameter['name']));
            $name = trim($aliases[0], '-');

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
                } else if ($argument === $alias) {
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

            if (($parameter['required'] ?? false) && !isset($result[$name])) {
                throw new \BadFunctionCallException(
                    "Missing required parameter '{$name}'"
                );
            }

            if (isset($parameter['default']) && !isset($result[$name])) {
                $result[$name] = $parameter['default'];

                continue;
            }

            if (!isset($result[$name]) || preg_match(static::TYPE_MAP[$parameter['type']], (string) $result[$name]) !== false) {
                continue;
            }

            if (!filter_var($result[$name], static::TYPE_MAP[$parameter['type']])) {
                throw new \InvalidArgumentException(
                    "The value '{$result[$name]}' for {$parameter} must be of type {$parameter['type']}"
                );
            }
        }

        return $result;
    }
}
