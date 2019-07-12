<?php declare(strict_types=1);
namespace Onion\Framework\Console\Components;

use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Progress
{
    private $length;
    private $steps;
    private $progress = 1;

    private $placeholder;
    private $filler;

    private $bar;
    private $ticks = 0;

    private $lastOutputLength = 0;

    private $format = '[{buffer}] ({progress}/{steps})';

    public function __construct(int $length, int $steps, $separators = ['-', '#'])
    {
        $this->length = $length;
        $this->steps = $steps;

        if (count($separators)< 2) {
            throw new \InvalidArgumentException('Number of separators must be 2 (placeholder, filler)');
        }
        list($this->placeholder, $this->filler)=$separators;
        $this->bar = round($steps/$length, 1);
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return int
     */
    public function getSteps(): int
    {
        return $this->steps;
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
        if ($this->progress !== $this->steps) {
            $this->progress = $progress;
        }
    }

    public function increment(int $progress)
    {
        if ($this->progress !== $this->steps) {
            $this->progress += $progress;
        }
    }

    public function display(ConsoleInterface $console)
    {
        $this->ticks = $this->progress/$this->bar;
        $buffer = str_repeat($this->filler, (int) $this->ticks);
        if ($this->progress === round($this->steps, 0)) {
            $this->ticks++;
        }

        $buffer .= '>';
        $buffer .= str_repeat(
            $this->placeholder,
            (int) ($this->length - strlen($buffer) > 0 ? $this->length - strlen($buffer) : 0)
        );

        $output = strtr($this->format, [
            '{buffer}' => substr($buffer, 0, $this->length),
            '{progress}' => str_pad((string) $this->progress, strlen((string) $this->steps), ' ', STR_PAD_LEFT),
            '{steps}' => $this->steps
        ]);

        $console->write(str_repeat(chr(8), $this->lastOutputLength));
        $this->lastOutputLength = $console->write($output);
    }
}
