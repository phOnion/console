<?php declare(strict_types=1);
namespace Onion\Framework\Console;

class Buffer implements Interfaces\BufferInterface
{
    private $content = '';

    private $stream;

    public function __construct(string $location)
    {
        $this->stream = fopen($location, 'wb');
    }

    public function read(int $length): string
    {
        return $this->content;
    }

    public function flush(): int
    {
        flock($this->stream, LOCK_EX);
        $length = fwrite($this->stream, $this->content);
        $this->content = '';
        flock($this->stream, LOCK_UN);

        return $length;
    }

    public function write(string $string): int
    {
        $this->content .= $string;

        return strlen($string);
    }
}