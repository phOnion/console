<?php declare(strict_types=1);

namespace Onion\Console\Application;

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
            $console->writeLine('No command provided', 'red');
            $console->writeLine('Try with --help to see list of available commands', 'cyan');
            return 1;
        }

        if (count($argv) === 1 && $argv[0] === '--help') {
            $console->writeLine('HELP', 'white');
            $console->writeLine('');
            foreach ($this->router->getAvailableCommands() as $command) {
                $this->displayHelpInfo($console, $command);
            }

            return 0;
        }

        if (count($argv) === 2 && $argv[1] === '--help') {
            if (in_array($argv[0], $this->router->getAvailableCommands(), true)) {
                $console->writeLine('HELP', 'white');
                $console->writeLine('');
                $this->displayHelpInfo($console, $argv[0]);

                return 0;
            }
        }

        list($command, $arguments)=$this->router->match((string) $argv[0], $argv);
        foreach ($arguments as $name => $value) {
            $console = $console->withArgument($name, $value);
        }

        return $command->trigger($console);
    }

    private function displayHelpInfo(ConsoleInterface $console, string $command)
    {
        $meta = $this->router->getCommandData($command);
        $console->write("COMMAND \t", 'white');
        $console->writeLine($command, 'bold-yellow');
        $console->writeLine("DESCRIPTION", 'white');
        $console->writeLine("\t" . $meta['description'], 'dark-gray');
        if (!empty($meta['flags'])) {
            $console->writeLine("FLAGS", 'white');
            foreach ($meta['flags'] as $flag => $description) {
                $console->writeLine("\t-$flag", 'dark-gray');
                $console->writeLine("\t    " . $description, 'dark-gray');

            }
        }
        if (!empty($meta['parameters'])) {
            $console->writeLine("PARAMETERS", 'white');
            foreach ($meta['parameters'] as $argument => $description) {
                $console->writeLine("\t--$argument", 'dark-gray');
                $console->writeLine("\t    " . $description, 'dark-gray');
                $console->writeLine('');
            }
        }
        $console->writeLine(PHP_EOL);
    }
}
