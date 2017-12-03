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
                        $console->write("%text:bold-gray%{$level['class']}{$level['type']}");
                    }
                    $console->write("%text:bold-cyan%{$level['function']}%text:gray%(");
                    foreach ($level['args'] as $index => $argument) {
                        if (!is_object($argument)) {
                            switch(gettype($argument)) {
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
                    $console->write("%text:gray%) @ ");
                    $console->writeLine("%text:white%{$level['file']}%text:gray%:%text:white%{$level['line']}");
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

        if (isset($argv[0]) && substr($argv[0], 0, 1) !== '-') {
            /**
             * @var $command CommandInterface
             * @var $arguments array
             */
            list($command, $arguments)=$this->router->match((string) $argv[0], $argv);
            foreach ($arguments as $name => $value) {
                $console = $console->withArgument($name, $value);
            }

            return $command->trigger($console);
        }

        $console->writeLine('%text:red%No command provided');
        $console->writeLine('%text:cyan%List of available commands');

        $console->writeLine('');
        foreach ($this->router->getAvailableCommands() as $command) {
            $this->displayHelpInfo($console, $command);
        }

        $console->write("%text:white%GLOBAL ARGUMENTS \t");
        $console->writeLine("%text:dark-gray%" . implode(' ',
                array_merge(
                    array_map(function ($value) {
                        return '[-' . $value . ']';
                    }, self::GLOBAL_FLAGS),
                    array_map(function ($value) {
                        return '[--' . $value . ']';
                    }, self::GLOBAL_PARAMS)
                )));

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
            $extra = implode(' ', array_map(function ($param) { return '<' . $param . '>'; }, $meta['extra'])) . ' ';
        }
        $console->write("%text:white%COMMAND \t");
        $console->writeLine("%text:bold-yellow%$command%text:dark-gray% $extra" . implode(' ',
                array_merge(
                    array_map(function ($value) {
                        return '[-' . $value . ']';
                    }, array_keys($meta['flags'])),
                    array_map(function ($value) {
                        return '[--' . $value . ']';
                    }, array_keys($meta['parameters']))
                )));
        $console->writeLine('%text:white%DESCRIPTION');
        $console->writeLine("\t%text:dark-gray%" . $meta['description']);



        if ($extra !== '' || !empty($meta['flags']) || !empty($meta['parameters'])) {
            if (!empty($meta['flags'])) {
                $console->writeLine('%text:white%FLAGS');
                foreach ($meta['flags'] as $flag => $description) {
                    $console->writeLine("\t%text:dark-gray%-$flag");
                    $console->writeLine("\t%text:dark-gray%    " . $description);
                    $console->writeLine('');
                }
            }
            if (!empty($meta['parameters'])) {
                $console->writeLine('%text:white%PARAMETERS');
                foreach ($meta['parameters'] as $argument => $description) {
                    $console->writeLine("\t%text:dark-gray%--$argument");
                    $console->writeLine("\t%text:dark-gray%    " . $description);
                    $console->writeLine('');
                }
            }
        }
        $console->writeLine(PHP_EOL);
    }

    public function __destruct()
    {
        restore_exception_handler();
    }
}
