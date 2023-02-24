<?php

declare(strict_types=1);

namespace Onion\Framework\Console;

use Onion\Framework\Console\Annotations\Command;
use Onion\Framework\Console\Annotations\Parameter;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Router\ArgumentParser;
use Onion\Framework\Console\Router\Router;
use Onion\Framework\Dependency\Interfaces\ContainerInterface;
use Psr\Container\ContainerInterface as ContainerContainerInterface;
use Onion\Framework\Dependency\Interfaces\BootableServiceProviderInterface;
use ReflectionAttribute;
use ReflectionObject;

class ConsoleServiceProvider implements BootableServiceProviderInterface
{
    public function register(ContainerInterface $provider): void
    {
        $provider->singleton(ArgumentParserInterface::class, new ArgumentParser());
        $provider->bind(Router::class, fn (ContainerContainerInterface $c) => new Router($c->get(ArgumentParserInterface::class)));
    }

    public function boot(ContainerInterface $container): void
    {
        $container->extend(Router::class, function (Router $r, ContainerInterface $c) {
            foreach ($c->tagged('command') as $command) {
                $reflection = new ReflectionObject($command);
                $attributes = $reflection->getAttributes(Command::class, ReflectionAttribute::IS_INSTANCEOF);

                if (empty($attributes)) continue;

                /** @var ?Command $cmd */
                $cmd = current($attributes)?->newInstance();
                if (!$cmd) continue;

                $r->addCommand($cmd->command, $command, [
                    'summary' => $cmd->summary,
                    'parameters' => array_map(fn (Parameter $p) => [
                        'name' => $p->name . ($p->aliases ? ' | ' . implode(' | ' . $p->aliases) : ''),
                        'description' => $p->description,
                        'type' => $p->type,
                        'default' => $p->default,
                    ], array_map(fn (ReflectionAttribute $r) => $r->newInstance(), $reflection->getAttributes(Parameter::class, ReflectionAttribute::IS_INSTANCEOF))),
                ]);
            }

            return $r;
        });
    }
}
