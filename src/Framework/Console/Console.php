<?php
declare(strict_types=1);

namespace Onion\Framework\Console;

use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Console implements ConsoleInterface
{
    private $pointer;
    private $flags = [];
    private $arguments = [];

    public function __construct(string $pointer)
    {
        $this->pointer = fopen($pointer, 'a+b');
    }

    public function withArgument(string $argument, string $value): ConsoleInterface
    {
        $self = clone $this;
        $self->arguments[$argument] = $value;

        return $self;
    }

    public function withoutArgument(string $argument): ConsoleInterface
    {
        $self = clone $this;
        if (!$this->hasArgument($argument)) {
            throw new \InvalidArgumentException(
                "Unable to remove argument '$argument', not provided"
            );
        }
        unset($self->arguments[$argument]);

        return $self;
    }

    public function hasArgument(string $argument): bool
    {
        return isset($this->arguments[$argument]);
    }

    public function getArgument(string $argument, string $default = null)
    {
        return $this->arguments[$argument] ?? $default;
    }

    public function block(
        string $message,
        int $width = 60,
        string $textColor = 'none',
        string $backgroundColor = 'none'
    ): int {
        $bytes = 0;

        $this->writeLine(' '. str_pad(' ', $width, ' ') . ' ', $textColor, $backgroundColor);
        foreach (str_split($message, $width) as $line) {
            $bytes += $this->writeLine(' '.str_pad($line, $width, ' ') . ' ', $textColor, $backgroundColor);
        }
        $this->writeLine(' '. str_pad(' ', $width, ' ') . ' ', $textColor, $backgroundColor);

        return $bytes;
    }

    public function write(string $message, string $textColor = 'none', string $backgroundColor = 'none'): int
    {
        return (int) fwrite($this->pointer, sprintf(
            "%s%s%s%s",
            self::BACKGROUND_COLORS[$backgroundColor],
            self::TEXT_COLORS[$textColor],
            $message,
            self::COLOR_TERMINATOR
        ));
    }

    public function writeLine(string $message, string $textColor = 'none', string $backgroundColor = 'none'): int
    {
        return (int) fwrite($this->pointer, sprintf(
                "%s%s%s%s",
                self::BACKGROUND_COLORS[$backgroundColor],
                self::TEXT_COLORS[$textColor],
                $message,
                self::COLOR_TERMINATOR
            ) . PHP_EOL);
    }

    public function prompt(string $message, string $textColor = 'none', string $backgroundColor = 'none'): string
    {
        $this->write($message . ': ', $textColor, $backgroundColor);
        return readline();
    }

    public function choice(
        string $message,
        string $default = 'n',
        string $truth = 'y',
        string $textColor = 'none',
        string $backgroundColor = 'none'
    ): bool {
        $response = $this->prompt(
            $message . ' [' . implode('/', array_map(function ($value) use ($default) {
                return $value === $default ? strtoupper($value) : $value;
            }, [
                self::PROMPT_YES,
                self::PROMPT_NO,
            ])) . ']',
            $textColor,
            $backgroundColor
        );

        return (
            strtolower($response)[0] === $truth || (empty($response) && $default === $truth)
        );
    }
}
