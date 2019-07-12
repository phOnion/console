<?php
namespace Onion\Framework\Console\Components;

class Box
{
    const TEXT_CONTAINED = 1;
    const TEXT_CONTINUATION = 2;

    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';
    const ALIGN_CENTER = 'center';

    private $length;
    private $horizontal;
    private $vertical;
    private $corner;
    private $align;

    private $lines = [];
    private $size;

    public function __construct(
        int $length,
        string $alignment = 'left',
        string $horizontal = '-',
        string $vertical = '|',
        string $corner = '+'
    ) {
        $this->align = $alignment;
        $this->length = $length;
        $this->horizontal = $horizontal;
        $this->vertical = $vertical;
        $this->corner = $corner;

    }

    public function addMessage(string $message, int $type = self::TEXT_CONTAINED)
    {
        $lines = explode(PHP_EOL, wordwrap($message, $this->length-4, PHP_EOL));

        $size = 0;
        $done = false;
        while (!$done) {
            $len = 0;
            foreach ($lines as $index => $line) {
                if ($len < strlen($line)) {
                    $len = strlen($line) + 2 + (strlen($this->vertical) * 2);
                }

                $lines[$index] = $line;
            }

            if ($len === $size) {
                $done = true;
            }

            $this->size = $size = $len;
        }

        switch ($type) {
            case static::TEXT_CONTAINED:
                $this->lines[] = $lines;
                break;
            case static::TEXT_CONTINUATION:
                $block = count($this->lines) - 1;
                $this->lines[$block] = array_merge($this->lines[$block], $lines);
                break;
            default:
                throw new \InvalidArgumentException("Unknown message type");
                break;
        }
    }

    public function __toString()
    {
        $padDirection = STR_PAD_RIGHT;
        switch ($this->align) {
            case static::ALIGN_RIGHT:
                $padDirection = STR_PAD_LEFT;
                break;
            case static::ALIGN_CENTER:
                $padDirection = STR_PAD_BOTH;
                break;
        }

        $lines = [];
        foreach ($this->lines as $index => $block) {
            array_unshift($block, str_pad(' ', $this->size));
            array_push($block, str_pad(' ', $this->size));
            if ($index === 0) {
                $lines[] = $this->corner . str_pad('', $this->size + 2, $this->horizontal) . $this->corner;
            }

            foreach ($block as $line) {
                $line = str_pad($line, $this->size, ' ', $padDirection);
                $lines[] = "{$this->vertical} {$line} {$this->vertical}";
            }

            $lines[] = $this->corner . str_pad('', $this->size+2, $this->horizontal) . $this->corner;
        }

        return implode(PHP_EOL, $lines);
    }
}
