<?php declare(strict_types=1);

namespace Onion\Console\Application;

use Onion\Console\Router\Router;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;

class Application
{
    const GLOBAL_FLAGS = ['q', 'v'];
    const GLOBAL_PARAMS = ['quiet', 'verbose', 'no-colors'];

    /** @var Router */
    private $router;

    public function __construct(Router $consoleRouter)
    {
        $this->router = $consoleRouter;
    }

    private function registerExceptionHandler(ConsoleInterface $console)
    {
        set_exception_handler(function (\Throwable $ex) use ($console) {
            if ($console->hasArgument('quiet')) {
                $console = $console->withoutArgument('quiet');
            }
            if ($console->hasArgument('q')) {
                $console = $console->withoutArgument('q');
            }
            $console->writeLine('%text:yellow%[ %text:red%ERROR%end%%text:yellow% ] - ' . $ex->getMessage());
            if ($console->getArgument('verbose', false) || $console->getArgument('v', false)) {
                $console->writeLine("%text:cyan%---------- TRACE --------");
                foreach ($ex->getTrace() as $index => $level) {
                    $console->write("#$index - ");
                    if (isset($level['class'])) {
                        $console->write("%text:italic-white%{$level['class']}{$level['type']}");
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

                        $console->write("%text:white%{$argument}" . ($index+1 === count($level['args']) ? '' : ', '));
                    }
                    $console->write("%text:white%) @ ");
                    $console->writeLine("%text:white%{$level['file']}%text:white%:%text:white%{$level['line']}");
                }
            }

            $console->writeLine('');
            return true;
        });
    }

    public function run(array $argv, ConsoleInterface $console): int
    {
        $globalArguments = $this->router->getArgumentParser()
            ->parse($argv, self::GLOBAL_FLAGS, self::GLOBAL_PARAMS);
        foreach ($globalArguments as $argument => $value) {
            $console = $console->withArgument($argument, $value);
        }
        $this->registerExceptionHandler($console);

        if (isset($argv[1]) && substr($argv[1], 0, 1) !== '-') {
            /**
             * @var $command CommandInterface
             * @var $arguments array
             */
            list($command, $arguments)=$this->router->match((string) $argv[1], $argv);
            foreach ($arguments as $name => $value) {
                $console = $console->withArgument($name, $value);
            }

            if ($console->getArgument('help', false)) {
                $this->displayHelpInfo($console, $argv[1]);
                return 0;
            }

            return $command->trigger($console);
        }

        $console->writeLine('%text:red%No command provided');
        $console->writeLine('%text:cyan%List of available commands');

        $console->write("%text:bold-white%GLOBAL ARGUMENTS \t");
        $console->writeLine("%text:white%" . implode(
            ' ',
            array_merge(
                array_map(function ($value) {
                        return '[-' . $value . ']';
                }, self::GLOBAL_FLAGS),
                array_map(function ($value) {
                        return '[--' . $value . ']';
                }, self::GLOBAL_PARAMS)
            )
        ));
        $console->writeLine('');
        foreach ($this->router->getAvailableCommands() as $command) {
            $this->displayHelpInfo($console, $command);
        }
        return 0;
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

            $extraLine .= " {$name}  ";
        }
        $console->writeLine($extraLine);

        $console->write("%text:bold-white%SUMMARY \t");
        $console->writeLine("%text:white%{$meta['summary']}");
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
                $console->writeLine("\t%text:white%" . $param['description']);
                $console->writeLine('');
            }
        }
        $console->writeLine(PHP_EOL);
    }

    public function __destruct()
    {
        restore_exception_handler();
    }
}
