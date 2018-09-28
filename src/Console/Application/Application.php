<?php declare(strict_types=1);

namespace Onion\Console\Application;

use Onion\Console\Router\Router;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;

class Application
{
    /** @var Router */
    private $router;

    public function __construct(Router $consoleRouter)
    {
        $this->router = $consoleRouter;
    }

    private function registerExceptionHandler(ConsoleInterface $console)
    {
        set_exception_handler(function (\Throwable $ex) use (&$console) {
            if ($console->hasArgument('quiet')) {
                $console = $console->withoutArgument('quiet');
            }

            $console->writeLine('%text:yellow%[ %text:red%ERROR%end%%text:yellow% ] - ' . $ex->getMessage());
            if ($console->getArgument('verbose', false) || $console->getArgument('v', false)) {
                $console->writeLine("%text:cyan%---------- TRACE --------");
                foreach ($ex->getTrace() as $index => $level) {
                    $console->write("#$index - ");
                    if (isset($level['class'])) {
                        $console->write("%text:italic-light-yellow%{$level['class']}%text:white%{$level['type']}");
                    }
                    $console->write("%text:bold-cyan%{$level['function']}%text:white%(");
                    foreach ($level['args'] as $index => $argument) {
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
                        $console->write("%text:white%{$argument}" . ($index+1 === count($level['args']) ? '' : ', '));
                    }
                    $console->write("%text:white%) @ ");
                    $console->writeLine("%text:white%{$level['file']}%text:white%:%text:white%{$level['line']}");
                }
            }

            $console->writeLine('');
            exit($ex->getCode() ?: 1);
        });
    }

    public function run(array $argv, ConsoleInterface $console): int
    {
        $this->registerExceptionHandler($console);

        if (isset($argv[1]) && substr($argv[1], 0, 1) !== '-') {
            /**
             * @var $command CommandInterface
             * @var $arguments array
             */
            list($command, $arguments)=$this->router->match((string) $argv[1], array_slice($argv, 2));

            foreach ($arguments as $name => $value) {
                $console = $console->withArgument(ltrim($name, '-'), $value);
            }
            $this->registerExceptionHandler($console);

            if ($console->getArgument('help', $console->getArgument('h'))) {
                exit($this->displayHelpInfo($console, $argv[1]));
            }

            exit($command->trigger($console));
        }

        $console->writeLine('%text:red%No command provided');
        $console->writeLine('%text:cyan%List of available commands');

        $console->writeLine('');
        foreach ($this->router->getAvailableCommands() as $command) {
            $this->displayHelpInfo($console, $command);
        }
        exit(0);
    }

    private function displayHelpInfo(ConsoleInterface $console, string $command)
    {
        /**
         * @var $meta array[]
         */
        $meta = $this->router->getCommandData($command);
        $extra = '';
        if ($meta['extra'] !== '') {
            $extra = implode(' ', array_map(function ($param) {
                return '<' . $param . '>';
            }, $meta['extra']));
        }
        $console->write("%text:bold-white%COMMAND \t");
        $extraLine = "%text:bold-yellow%{$command}%text:white% {$extra}";
        foreach ($meta['parameters'] as $name => $param) {
            $default = !($param['default'] ?? false) ? '' : " = {$param['default']}";
            $name = !($param['required'] ?? false) ? "[{$name}{$default}]" : "{$name}{$default}";

            $extraLine .= " {$name}";
        }
        $console->writeLine($extraLine);

        $console->write("%text:bold-white%SUMMARY \t");
        $console->writeLine("%text:white%{$meta['summary']}");

        if ($console->hasArgument('compact-output')) {
            return 0;
        }

        if (strlen($meta['description']) > 0) {
            $console->writeLine('%text:bold-white%DESCRIPTION');
            $console->writeLine("\t%text:white%" . $meta['description']);
        }

        if ($extra !== '' || !empty($meta['parameters'])) {
            $console->writeLine('%text:bold-white%ARGUMENTS');
            foreach ($meta['parameters'] as $name => $param) {
                $default = !isset($param['default']) ? '' : "={$param['default']}";
                $required = !($param['required'] ?? false) ? '' : '%text:red%(REQUIRED)';

                $console->writeLine(
                    "    %text:cyan%{$param['type']}\t%text:green%$name%text:green%{$default} {$required}"
                );
                if (isset($param['description'])) {
                    $console->writeLine("\t%text:white%" . $param['description']);
                }
                $console->writeLine('');
            }
        }
        $console->writeLine(PHP_EOL);

        return 0;
    }

    public function __destruct()
    {
        restore_exception_handler();
    }
}
