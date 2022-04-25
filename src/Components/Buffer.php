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

    public function clear()
    {
        $this->size = 0;
        fseek($this->content, 0);
        ftruncate($this->content, 0);
    }

    public function add(string $text): bool
    {
        $size = fwrite($this->content, $text);
        $this->size += $size;

        return $size === strlen($text);
    }

    public function addLine(string $line): bool
    {
        return $this->add("{$line}\n");
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
