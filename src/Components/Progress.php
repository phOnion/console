<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Components;

use Onion\Framework\Console\Interfaces\ComponentInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Progress implements ComponentInterface
{
    private int $progress = 1;

    private readonly float $bar;
    private array $ticks = [];

    private int $lastTick = 0;

    private $format = '{complete}{cursor}{placeholder} {progress}% ({current}/{total})';

    public function __construct(
        private readonly int $length,
        private readonly int $steps,
        private readonly string $placeholder = " ",
        private readonly string $filler = "<color bg='green'> </color>",
        private readonly string|Animation $cursor = "<color text='green'>\u{e0b0}</color>"
    ) {
        $this->bar = round($steps / $this->length, 2);
    }

    private function strlen(string $string): int
    {
        return function_exists('mb_strlen') ? mb_strlen($string) : strlen($string);
    }

    /**
     * @return int
     */
    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setFormat(string $format)
    {
        $this->format = $format;
    }

    public function update(int $progress)
    {
        if ($this->lastTick > 0) {
            $this->ticks[] = time() - $this->lastTick;
        }

        $this->lastTick = time();

        if ($this->progress !== $this->steps) {
            $this->progress = $progress;
        }
    }

    public function increment(int $progress)
    {
        if ($this->lastTick > 0) {
            $this->ticks[] = time() - $this->lastTick;
        }

        $this->lastTick = time();

        if ($this->progress !== $this->steps) {
            $this->progress += $progress;
        }
    }

    public function advance(): void
    {
        $this->increment(1);
    }

    public function flush(ConsoleInterface $console): void
    {
        $inProgress = $this->progress !== $this->steps;
        $ticks = (int) floor($this->progress / $this->bar) - (int) $inProgress;
        $fills = (int) ceil(($this->steps - $this->progress) / $this->bar);

        $average = array_sum($this->ticks) / (count($this->ticks) ?: 1) ?: 1;
        $remaining = $average > 0 ? ($this->steps - $this->progress) * $average : 0;

        $this->overwrite($console, strtr($this->format, [
            '{complete}' => str_repeat($this->filler, $ticks),
            '{placeholder}' => str_repeat($this->placeholder, $fills),
            '{cursor}' => $inProgress ? ($this->cursor instanceof Animation ? $this->cursor->getContents() : $this->cursor) : '',
            '{current}' => str_pad((string) $this->progress, $this->strlen((string) $this->steps), ' ', STR_PAD_LEFT),
            '{total}' => $this->steps,
            '{progress}' => number_format(($this->progress / $this->steps) * 100, 0),
            '{eta}' => $inProgress ? $this->normalizeSeconds($remaining) : 'DONE',
        ]));
    }

    public function overwrite(ConsoleInterface $console, string $output): void
    {
        $console->write("\x1b[1G\x1b[2K{$output}");
    }

    private function normalizeSeconds(float $time): string
    {
        if ($time < 1) {
            return '<1sec';
        }

        $hours = floor($time / 3600);
        $minutes = floor(($time / 60)) % 60;
        $seconds = floor($time) % 60;

        return ($hours > 0 ? "{$hours}hrs " : '') .
            ($minutes > 0 ? "{$minutes}min " : '') .
            ($seconds > 0 ? "{$seconds}sec" : '');
    }
}
