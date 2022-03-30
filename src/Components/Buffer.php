<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Components;

use Onion\Framework\Console\Interfaces\ComponentInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Buffer implements ComponentInterface
{
    /** @var resource */
    private mixed $content;
    private int $size = 0;

    public function __construct()
    {
        $this->content = fopen('php://temp', 'r+b');
    }

    public function addLine(string $line): bool
    {
        $size = fwrite($this->content, $line . "\n");
        $this->size += $size;

        return $size === strlen($line);
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function flush(ConsoleInterface $console): void
    {
        fseek($this->content, 0);
        while (!feof($this->content)) {
            $console->write(fread($this->content, 1024));
        }
    }
}
