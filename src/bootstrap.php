<?php

use Onion\Framework\Console\Router\ArgumentParser;
use Onion\Framework\Console\Console;

if (php_sapi_name() != "cli") {
    return;
}

set_error_handler(function ($level, $message, $file, $line) {
    $stream = 'php://stdout';
    switch ($level) {
        case E_USER_WARNING:
        case E_WARNING:
            $stream = 'php://stderr';
            $type = 'WARNING';
            $color = 'yellow';
            break;
        case E_USER_NOTICE:
        case E_NOTICE:
        case E_USER_DEPRECATED:
            $type = 'NOTICE';
            $color = 'cyan';
            break;
        default:
            $stream = 'php://stderr';
            $type = 'ERROR';
            $color = 'red';
            break;
    }

    $current = error_reporting();
    if (($current & $level) !== $level) {
        return;
    }

    $console = new Console(fopen($stream, 'wb'));
    $console->writeLine("<color text='white'>[ <color text='{$color}'>$type</color> ]</color> - {$message} - {$file}:{$line}");
    $console->writeLine('');
}, E_ALL);
set_exception_handler(function (\Throwable $ex) {
    global $argv;

    $argumentParser = new ArgumentParser();
    $args = $argumentParser->parse($argv, [
        ['name' => '--verbose | -vvv', 'type' => 'bool'],
    ]);

    $console = new Console(STDERR);
    foreach ($args as $name => $value) {
        $console = $console->withArgument($name, $value);
    }

    $console->writeLine('<color text="yellow">[ <color text="red">ERROR</color> ]</color> - ' . $ex->getMessage());
    if ($console->getArgument('verbose', false) || $console->getArgument('v', false)) {
        $console->writeLine('<color text="cyan">---------- TRACE --------</color>');
        foreach ($ex->getTrace() as $index => $level) {
            $level['line'] ??= 0;
            $level['file'] ??= '<unknown>';

            $console->write("#$index - ");

            if (isset($level['class']) !== false) {
                $console->write("<color text=\"italic-light-yellow\">{$level['class']}</color>");
                $console->write("<color text=\"white\">{$level['type']}</color>");
            }

            $console->write("<color text=\"bold-cyan\">{$level['function']}</color><color text=\"white\">(</color>");
            foreach ($level['args'] ?? [] as $index => $argument) {
                if (!is_object($argument)) {
                    switch (gettype($argument)) {
                        case 'string':
                            $argument = "'$argument'";
                            break;
                        case 'array':
                            $argument = json_encode($argument);
                            break;
                        case 'null':
                            $argument = 'null';
                            break;
                        case 'resource':
                        case 'resource (closed)':
                            $argument = 'resource:' . get_resource_type($argument);
                            break;
                        case 'boolean':
                            $argument = $argument ? 'true' : 'false';
                            break;
                    }
                }

                $argument = is_object($argument) ? get_class($argument) . '::object' : $argument;
                $argument = str_replace('\\\\', '\\', $argument);
                $console->write("<color text=\"white\">{$argument}" . ($index + 1 === count($level['args']) ? '' : ', ') . '</color>');
            }
            $console->write('<color text="white"> @ </color>');
            $console->writeLine("<color text=\"white\">{$level['file']}:{$level['line']}</color>");
        }
    }

    $console->writeLine('');
    exit($ex->getCode() ?: 1);
});
