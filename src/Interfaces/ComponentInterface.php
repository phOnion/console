<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Interfaces;

interface ComponentInterface
{
    public function flush(ConsoleInterface $console): void;
}
