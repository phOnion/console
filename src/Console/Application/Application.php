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

        if (count($argv) === 1 && in_array('--help', $argv, true)) {
            foreach ($this->router->getAvailableCommands() as $command) {
                $meta = $this->router->getCommandData($command);
                $console->write("\tCommand: \t", 'yellow');
                $console->writeLine($command, 'bold-green');
                $console->write("\tDescription: \t", 'yellow');
                $console->writeLine($meta['description'], 'italic-cyan');
                $console->writeLine('');
                if (!empty($meta['flags'])) {
                    $console->writeLine("\tFlags:", 'yellow');
                    foreach ($meta['flags'] as $flag => $description) {
                        $console->write("\t\t-$flag", 'bold-green');
                        $console->writeLine("\t\t" . $description, 'cyan');
                        $console->writeLine('');
                    }
                }
                if (!empty($meta['parameters'])) {
                    $console->writeLine("\tParameters:", 'yellow');
                    foreach ($meta['parameters'] as $argument => $description) {
                        $console->write("\t\t--$argument", 'bold-green');
                        $console->writeLine("\t" . $description, 'cyan');
                        $console->writeLine('');
                    }
                }
            }

            return 0;
        }

        list($command, $arguments)=$this->router->match((string) $argv[0], $argv);
        foreach ($arguments as $name => $value) {
            $console = $console->withArgument($name, $value);
        }

        return $command->trigger($console);
    }
}
