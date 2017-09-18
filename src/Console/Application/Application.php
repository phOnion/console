<?php declare(strict_types=1);

namespace Onion\Console\Application;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Console\Router\Router;

class Application
{
    private $router;
    public function __construct(Router $consoleRouter)
    {
        $this->router = $consoleRouter;
    }

    public function run(array $argv, ConsoleInterface $console): int
    {
        if (!isset($argv[0])) {
            $console->writeLine('%textColor:red%No command provided');
            $console->writeLine('%textColor:cyan%Try with --help to see list of available commands');
            return 1;
        }

        if (count($argv) === 1 && $argv[0] === '--help') {
            $console->writeLine('%textColor:white%HELP');
            $console->writeLine('');
            foreach ($this->router->getAvailableCommands() as $command) {
                $this->displayHelpInfo($console, $command);
            }

            return 0;
        }

        if (
            count($argv) === 2 &&
            $argv[1] === '--help' &&
            in_array($argv[0], $this->router->getAvailableCommands(), true)
        ) {
            $console->writeLine('%textColor:white%HELP');
            $console->writeLine('');
            $this->displayHelpInfo($console, $argv[0]);

            return 0;
        }

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

    private function displayHelpInfo(ConsoleInterface $console, string $command)
    {
        /**
         * @var $meta array[]
         */
        $meta = $this->router->getCommandData($command);
        $console->write("%textColor:white%COMMAND \t");
        $console->writeLine("%textColor:bold-yellow%$command");
        $console->writeLine('%textColor:white%DESCRIPTION');
        $console->writeLine("\t%textColor:dark-gray%" . $meta['description']);

        $extra = '';
        if ($meta['extra'] !== '') {
            $extra = implode(' ', array_map(function ($param) { return '<' . $param . '>'; }, $meta['extra'])) . ' ';
        }

        if ($extra !== '' && !empty($meta['flags']) && !empty($meta['parameters'])) {
            $console->writeLine("\t%textColor:dark-gray%" . $extra . implode(' ',
                    array_merge(
                        array_map(function ($value) {
                            return '[-' . $value . ']';
                        }, array_keys($meta['flags'])),
                        array_map(function ($value) {
                            return '[--' . $value . ']';
                        }, array_keys($meta['parameters']))
                    ))
            );
            if (!empty($meta['flags'])) {
                $console->writeLine('%textColor:white%FLAGS');
                foreach ($meta['flags'] as $flag => $description) {
                    $console->writeLine("\t%textColor:dark-gray%-$flag");
                    $console->writeLine("\t%textColor:dark-gray%    " . $description);
                    $console->writeLine('');
                }
            }
            if (!empty($meta['parameters'])) {
                $console->writeLine('%textColor:white%PARAMETERS');
                foreach ($meta['parameters'] as $argument => $description) {
                    $console->writeLine("\t%textColor:dark-gray%--$argument");
                    $console->writeLine("\t%textColor:dark-gray%    " . $description);
                    $console->writeLine('');
                }
            }
        }
        $console->writeLine(PHP_EOL);
    }
}
