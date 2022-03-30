<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Router;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Interfaces\CommandInterface;

class Router
{
    private array $commands;
    private array $handlers = [];

    public function __construct(public readonly ArgumentParserInterface $argumentParser)
    {
    }

    public function getAvailableCommands(): array
    {
        return array_keys($this->handlers);
    }

    public function getCommandData(string $command): array
    {
        return $this->commands[$command];
    }

    public function addCommand(string $name, CommandInterface $command, array $data = [])
    {
        $params = [];
        if (strpos($name, ' ') !== false) {
            list($name, $extra) = explode(' ', $name, 2);
            $params = explode(' ', $extra);
        }

        $this->commands[$name] = array_merge($data, [
            'extra' => array_map(function ($param) {
                return trim($param, '[]');
            }, $params)
        ]);
        $this->handlers[$name] = $command;
    }

    public function match(string $command, array $arguments = []): array
    {
        if (!isset($this->handlers[$command])) {
            throw new \RuntimeException("Command '$command' not found");
        }

        $options = $this->argumentParser->parse($arguments, $this->commands[$command]['parameters'] ?? []);
        if ($this->commands[$command]['extra'] !== null) {
            foreach ($this->commands[$command]['extra'] as $param) {
                $options[$param] = array_shift($arguments);
            }
        }

        return [$this->handlers[$command], $options];
    }
}
