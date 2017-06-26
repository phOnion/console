<?php declare(strict_types=1);

namespace Onion\Console\Router;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
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

    private $argumentParser;
    public function __construct(ArgumentParserInterface $argumentParser)
    {
        $this->argumentParser = $argumentParser;
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
        $param = null;
        if (strpos($name,' ') !== false) {
            list($name, $param)=explode(' ', $name, 2);
        }
        $this->commands[$name] = array_merge($data, ['extra' => trim($param ?? '', '[]')]);
        $this->handlers[$name] = $command;
    }

    public function match(string $command, array $arguments = []): array
    {
        if (!isset($this->handlers[$command])) {
            throw new \RuntimeException("Command '$command' not found");
        }

        $flags = array_keys($this->commands[$command]['flags']);
        $params = array_keys($this->commands[$command]['parameters']);

        $options = $this->argumentParser->parse($arguments, $flags, $params);
        if ($this->commands[$command]['extra'] !== null && strpos($arguments[1] ?? '', '-') !== 0) {
            $options[$this->commands[$command]['extra']] = $arguments[1] ?? null;
        }

        return [$this->handlers[$command], $options];
    }
}
