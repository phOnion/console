<?php declare(strict_types=1);

namespace Onion\Framework\Console\Interfaces;

/**
 * Interface CommandInterface
 *
 * @package Onion\Framework\Console\Interfaces
 */
interface CommandInterface
{
    /**
     * @param ConsoleInterface $console The currently active console
     *
     * @return int The return code of the call
     */
    public function trigger(ConsoleInterface $console);
}
