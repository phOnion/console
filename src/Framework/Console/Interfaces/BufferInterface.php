<?php declare(strict_types=1);
namespace Onion\Framework\Console\Interfaces;

interface BufferInterface
{
    public function write(string $string): int;
    public function read(int $length): string;
    public function flush(): int;
}