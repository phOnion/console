<?php declare(strict_types=1);

namespace Onion\Console\Router;

use Onion\Framework\Console\Interfaces\CommandInterface;

class Router
{
    /**
     * @var array
     */
    private $commands;

    /**
     * @var CommandInterface[]
     */
    private $handlers;

    public function addCommand(string $name, CommandInterface $command, array $data = [])
    {
        $this->commands[$name] = $data;
        $this->handlers[$name] = $command;
    }

    public function match(string $command): array
    {
        if (!isset($this->handlers[$command])) {
            throw new \RuntimeException("Command '$command' not found");
        }

        $flags = array_keys($this->commands[$command]['flags']);
        $arguments = array_keys($this->commands[$command]['arguments']);

        /*
         * Flags and arguments should use the notation:
         *  + !flag - For required flag - !o
         *  + ?flag - For optional flag - ?v
         *  + !argument - For required argument - !output
         *  + ?argument - For optional argument - ?verbose
         */

        $options = [];

        return [$this->handlers[$command], $options];
    }
}
