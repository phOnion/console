<?php declare(strict_types=1);

namespace Onion\Console\Router;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Interfaces\CommandInterface;

class Router
{
    private const GLOBAL_PARAMS = [
        '--quiet | -q' => [
            'type' => 'bool',
            'description' => 'Suppress all command output'
        ],
        '--verbose | -v' => [
            'type' => 'bool',
            'description' => 'Indicate that the command may output extended information'
        ],
        '--no-colors' => [
            'type' => 'bool',
            'description' => 'Disable all color outputs'
        ],
        '--help | -h' => [
            'type' => 'bool',
            'description' => 'Display help info',
        ],
        '--compact-output | --minimal-output' => [
            'type' => 'bool',
            'description' => 'Indicate that only minimal output should be emitted by the command'
        ]
    ];

    /**
     * @var array
     */
    private $commands;

    /**
     * @var CommandInterface[]
     */
    private $handlers = [];

    /**
     * @var ArgumentParserInterface
     */
    private $argumentParser;

    public function __construct(ArgumentParserInterface $argumentParser)
    {
        $this->argumentParser = $argumentParser;
    }

    public function getArgumentParser(): ArgumentParserInterface
    {
        return $this->argumentParser;
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
            list($name, $extra)=explode(' ', $name, 2);
            $params = explode(' ', $extra);
        }

        foreach (self::GLOBAL_PARAMS as $cmd => $definition) {
            $data['parameters'][$cmd] = $definition;
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
        $options = $this->argumentParser->parse($arguments, $this->commands[$command]['parameters']);
        if ($this->commands[$command]['extra'] !== null) {
            foreach ($this->commands[$command]['extra'] as $param) {
                $options[$param] = array_shift($arguments);
            }
        }

        return [$this->handlers[$command], $options];
    }
}
