<?php

namespace Onion\Framework\Console\Components;

use Onion\Framework\Console\Components\Types\BoxAlignmentType;

class Box extends Buffer
{
    public function __construct(
        private readonly int $length,
        private readonly BoxAlignmentType $alignment = BoxAlignmentType::LEFT,
        private readonly string $horizontal = '',
        private readonly string $vertical = '',
        private readonly string $corner = ''
    ) {
        parent::__construct();
    }

    public function separator()
    {
        $this->addLine(sprintf(
            '%s%s%s',
            $this->corner,
            str_repeat(
                $this->horizontal,
                $this->length - (strlen($this->corner) * 2)
            ),
            $this->corner
        ));
    }
    public function addMessage(string $message): void
    {
        $lineOffset = (strlen($this->vertical) * 2);
        $lines = explode("\n", wordwrap($message, $this->length - $lineOffset, cut_long_words: true));

        foreach ($lines as $line) {
            $this->addLine(sprintf(
                '%s%s%s',
                $this->vertical,
                str_pad($line, $this->length - (strlen($this->vertical) * 2), ' ', $this->alignment->value),
                $this->vertical
            ));
        }
    }
}
