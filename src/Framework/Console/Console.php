<?php declare(strict_types=1);

namespace Onion\Framework\Console;

use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\BufferInterface;

class Console implements ConsoleInterface
{
    private $buffer;
    private $autoFlush = true;

    private $arguments = [];

    public function __construct(BufferInterface $buffer, bool $autoFlush = true)
    {
        $this->buffer = $buffer;
        $this->autoFlush = $autoFlush;
    }

    public function getBuffer(): BufferInterface
    {
        return $this->buffer;
    }

    public function withArgument(string $argument, $value): ConsoleInterface
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

    public function getArgument(string $argument, $default = null)
    {
        return $this->arguments[$argument] ?? $default;
    }

    public function block(
        string $message,
        int $width = 60
    ): int {
        $bytes = 0;

        $this->writeLine('');
        foreach (str_split($this->normalizeText($message), $width) as $line) {
            $bytes += $this->writeLine(' '.str_pad(trim($line), $width, ' ', STR_PAD_RIGHT) . ' ');
        }
        $this->writeLine('');

        return $bytes;
    }

    public function write(string $message): int
    {
        $message = $this->normalizeText("$message");

        if ($this->getArgument('no-ascii', false) || $this->getArgument('no-colors', false)) {
            $message = $this->clearMessage($message);
        }

        $this->buffer->write("$message");

        if ((!$this->getArgument('quiet', false) && !$this->getArgument('q', false)) && $this->autoFlush) {
            $this->buffer->flush();
        }

        return strlen($this->clearMessage($message));
    }

    public function writeLine(string $message): int
    {
        return (int) $this->write($message . PHP_EOL);
    }

    public function password(string $message): string
    {
        $this->write(sprintf('%s: ', $message));
        $this->buffer->flush();
        if (stripos(PHP_OS, 'win') === false) {
            system('stty -echo');
            $result = trim(fgets(STDIN));
            system('stty echo');
        } else {
            $location = sys_get_temp_dir() . '/hiddeninput.exe';
            if (!file_exists($location)) {
                $this->writeLine('Downloading password polyfill');
                $fp = fopen($location, 'wb');
                $dest = fopen('https://github.com/Seldaek/hidden-input/blob/master/build/hiddeninput.exe?raw=true', 'rb');
                stream_copy_to_stream($dest, $fp);
                fclose($dest);
            }
            $result = exec($location) ;
            $this->writeLine('');
        }

        return $result;
    }

    public function prompt(string $message, string $default = ''): string
    {
        if ($default !== '') {
            $message .= "($default)";
        }

        $this->write("$message: ");
        return trim((string) fgets(STDIN)) ?: $default;
    }

    public function confirm(
        string $message,
        string $default = ''
    ): bool {
        $response = $this->choice($message, array_map(function ($val) use ($default) {
                return $default === $val ? ucfirst($val) : strtoupper($val);
            }, ['y', 'n']), $default);

        return (strtolower($response) === 'y');
    }

    public function choice(
        string $message,
        array $options,
        string $default = ''
    ): string
    {
        $response = $this->prompt(
            $message . '['. implode(', ', array_map('strtolower', $options)) . ']',
            $default
        );

        if (!in_array($response, $options)) {
            $response = $this->choice($message, $options, $default);
        }

        return $response;
    }

    public function normalizeText(string $message): string
    {
        if (strpos($message, '%text') !== false) {
            preg_match_all('#%text:([a-zA-Z-]+)%#i', $message, $matches);
            $message = strtr($message, array_combine($matches[0], array_map(function ($value) {
                return self::TEXT_COLORS[$value];
            }, $matches[1])));
        }

        if (strpos($message, '%bg') !== false) {
            preg_match_all('#%bg:([a-zA-Z-]+)%#i', $message, $matches);
            $message = strtr($message, array_combine($matches[0], array_map(function ($value) {
                return self::BACKGROUND_COLORS[$value];
            }, $matches[1])));
        }

        return strtr(
            "$message%end%",
            ['%end%' => self::COLOR_TERMINATOR]
        );
    }

    public function clearMessage(string $message)
    {
        return preg_replace("#(\033\[[0-9;]*m)#i", '', $message);
    }
}
