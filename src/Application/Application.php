<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Application;

use Onion\Framework\Console\Router\Router;
use Onion\Framework\Console\Components\Box;
use Onion\Framework\Console\Components\Types\BoxAlignmentType;
use Onion\Framework\Console\Interfaces\{ApplicationInterface, ArgumentParserInterface, ConsoleInterface};

class Application implements ApplicationInterface
{
    private const GLOBAL_PARAMS = [[
        'name' => '--quiet | -q',
        'type' => 'bool',
        'description' => 'Suppress all command output'
    ], [
        'name' => '--verbose | -vvv',
        'type' => 'bool',
        'description' => 'Indicate that the command may output extended information'
    ], [
        'name' => '--no-colors | --no-color',
        'type' => 'bool',
        'description' => 'Indicate that the command output should not include colors'
    ]];

    public function __construct(
        private readonly Router $router,
        private readonly ArgumentParserInterface $argumentParser
    ) {
    }

    public function run(array $argv, ConsoleInterface $console)
    {
        $args = array_slice($argv, 2);
        if (isset($argv[1]) && substr($argv[1], 0, 1) !== '-') {
            foreach ($this->argumentParser->parse($args, static::GLOBAL_PARAMS) as $name => $value) {
                $console = $console->withArgument(ltrim($name, '-'), $value);
            }
            /**
             * @var $command CommandInterface
             * @var $arguments array
             */
            [$command, $arguments] = $this->router->match((string) $argv[1], $args);
            foreach ($arguments as $name => $value) {
                $console = $console->withArgument(ltrim($name, '-'), $value);
            }

            return $command->trigger($console);
        }

        $console->writeLine('<color text="red">No command provided</color>');
        $console->writeLine('');
        $console->writeLine('<color text="cyan">List of available commands</color>');

        $console->writeLine('');
        foreach ($this->router->getAvailableCommands() as $command) {
            $this->displayHelpInfo($console, $command);
        }
    }

    private function displayHelpInfo(ConsoleInterface $console, string $command)
    {
        $meta = $this->router->getCommandData($command);
        $extra = '';
        if ($meta['extra'] !== '') {
            $extra = implode(' ', array_map(function ($param) {
                return '<' . $param . '>';
            }, $meta['extra']));
        }
        $console->write("<color text='white' decoration='bold'>COMMAND \t</color>");
        $console->writeLine(
            "<color text='yellow' decoration='bold'>{$command}</color><color text='white'> {$extra} </color>"
        );

        if (isset($meta['summary'])) {
            $console->write("<color text='white' decoration='bold'>SUMMARY \t</color>");
            $console->writeLine("<color text='white'>{$meta['summary']}</color>");
        }

        if ($console->getArgument('quiet', false)) {
            return 0;
        }

        if (isset($meta['description']) && strlen($meta['description']) > 0) {
            $console->writeLine('<color text="white" decoration="bold">DESCRIPTION</color>');
            $box = new Box(80, vertical: "\t");
            $box->addMessage("<color text='white'>{$meta['description']}</color>");
            $box->separator();
            $box->flush($console);
        }

        if ($extra !== '' || !empty($meta['parameters'] ?? [])) {
            $console->writeLine('<color text="white" decoration="bold">ARGUMENTS</color>');
            foreach ($meta['parameters'] as $param) {
                $param['type'] = str_pad($param['type'], 5, ' ');
                $required = !($param['required'] ?? false) ? '' : '<color text="red">(REQUIRED)</color>';
                $param['default'] ??= '';
                $default = $param['default'] !== '' ? '=' . match ($param['type']) {
                    'bool' => $param['default'] ? 'true' : 'false',
                    'boolean' => $param['default'] ? 'true' : 'false',
                    'string' => "\"{$param['default']}\"",
                    default => $param['default'],
                }
                    : '';

                $console->write("\t<color text='cyan'>{$param['type']}</color>");
                $console->write("\t<color text='green'>{$param['name']}</color>");
                $console->write("<color text='blue'>{$default}</color>");
                $console->write($required);
                $console->writeLine('');

                if (isset($param['description'])) {
                    $box = new Box(80, BoxAlignmentType::LEFT, vertical: "\t");
                    $box->addMessage("<color text='white'>{$param['description']}</color>");
                    $box->flush($console);
                }
                $console->writeLine('');
            }
        }
        $console->writeLine(PHP_EOL);

        return 0;
    }

    public function __destruct()
    {
        restore_exception_handler();
    }
}
