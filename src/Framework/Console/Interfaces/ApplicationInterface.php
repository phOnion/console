<?php declare(strict_types=1);
namespace Onion\Framework\Console\Interfaces;

use Onion\Framework\Loop\Coroutine;

interface ApplicationInterface
{
    public function run(array $argv, ConsoleInterface $console): Coroutine;
}
