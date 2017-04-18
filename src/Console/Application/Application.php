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
            return 1;
        }
        list($command, $arguments)=$this->router->match((string) $argv[0]);

        return 0;
    }
}
