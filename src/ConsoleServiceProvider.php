<?php

declare(strict_types=1);

namespace Onion\Framework\Console;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Router\ArgumentParser;
use Onion\Framework\Console\Router\Router;
use Onion\Framework\Dependency\Interfaces\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\ServiceProviderInterface;
use Onion\Framework\Router\Interfaces\RouterInterface;
use Psr\Container\ContainerInterface as ContainerContainerInterface;

class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $provider): void
    {
        $provider->singleton(ArgumentParserInterface::class, new ArgumentParser());
        $provider->bind(RouterInterface::class, fn (ContainerContainerInterface $c) => new Router($c->get(ArgumentParserInterface::class)));
    }
}
