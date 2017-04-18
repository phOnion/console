<?php
declare(strict_types=1);

namespace Onion\Framework\Console\Factory;

use Onion\Framework\Console\Console;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface as Container;

class ConsoleFactory implements FactoryInterface
{
    /**
     * Method that is called by the container, whenever a new
     * instance of the application is necessary. It is the only
     * method called when creating instances and thus, should
     * produce/return the fully configured object it is intended
     * to build.
     *
     * @param Container $container
     *
     * @return mixed
     */
    public function build(Container $container)
    {
        return new Console($container->get('console.stream'));
    }
}
