<?php declare(strict_types=1);

namespace Onion\Console\Router\Factory;

use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Console\Router\Router;
use Psr\Container\ContainerInterface as Container;

class RouterFactory implements FactoryInterface
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
     * @return Router
     */
    public function build(Container $container): Router
    {
        $commands = $container->get('routes');
        $router = new Router(
            $container->get(ArgumentParserInterface::class)
        );
        foreach ($commands as $command) {
            $command = array_merge([
                'flags' => [],
                'arguments' => [],
            ], $command);

            $name = $command['name'];
            $handler = $container->get($command['handler']);
            unset($command['name'], $command['handler']);

            $router->addCommand($name, $handler, $command);
        }
        return $router;
    }
}
