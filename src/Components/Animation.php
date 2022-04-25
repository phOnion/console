<?php

namespace Onion\Framework\Console\Components;

use Closure;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Animation extends Buffer
{
    private int $frame = 0;
    private readonly int $frameCount;

    public function __construct(private readonly array $frames, private ?Closure $decorator = null)
    {
        $this->decorator ??= fn (string $output) => $output;
        $this->frameCount = count($frames);

        parent::__construct();
    }

    public function getContents(): string
    {
        return ($this->decorator)($this->frames[$this->frame++ % $this->frameCount]);
    }

    public function flush(ConsoleInterface $console): void
    {
        $this->add($this->getContents());
        parent::flush($console);
        $this->clear();
    }
}
