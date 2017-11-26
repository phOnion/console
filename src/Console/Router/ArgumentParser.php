<?php declare(strict_types=1);

namespace Onion\Console\Router;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;

class ArgumentParser implements ArgumentParserInterface
{
    public function parse(array $arguments, array $flags = [], array $parameters = []): array
    {
        $result = [];
        for ($i=0, $count=count($arguments); $i<$count; $i++) {
            $argument = $arguments[$i];

            if (strpos($argument, '--') === 0) {
                $name = substr($argument, 2);
                if (strpos($name, '=')) {
                    list($name,$value)=explode('=', $name, 2);
                    if (in_array($name, $parameters, true)) {
                        $result[$name] = $value;
                    }
                } else {
                    if (isset($arguments[$i+1]) && strpos($arguments[$i+1], '-') !== 0) {
                        $result[$name] = $arguments[++$i];
                    } else {
                        $result[$name] = true;
                    }
                }
            }

            if (strpos($argument, '-') === 0) {
                $name = substr($argument, 1, 1);
                if (in_array($name, $flags, true)) {
                    if (strlen($argument) > 2) {
                        $result[$name] = substr($argument, 2);
                    }

                    if (strlen($argument) === 2) {
                        if (isset($arguments[$i+1]) && strpos($arguments[$i+1], '-') !== 0) {
                            $result[$name] = $arguments[++$i];
                        } else {
                            $result[$name] = true;
                        }
                    }
                }

            }
        }

        return $result;
    }
}
