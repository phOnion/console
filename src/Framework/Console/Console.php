<?php declare(strict_types=1);

namespace Onion\Framework\Console;

use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Seld\CliPrompt\CliPrompt;

class Console implements ConsoleInterface, \SplObserver
{
    private $stream;
    private $arguments = [];

    public function __construct($stream)
    {
        $this->stream = $stream;
    }

    public static function create($stream): self
    {
        return new static($stream);
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

    public function getArguments(): array
    {
        return $this->arguments ?? [];
    }

    public function block(
        string $message,
        int $width = 60
    ): int {
        return $this->writeLine(wordwrap($message, $width, PHP_EOL) . PHP_EOL);
    }

    public function write(string $message): int
    {
        if ($this->hasArgument('quiet')) {
            return 0;
        }

        $message = $this->normalizeText($message);

        $cliColorEnv = getenv('CLICOLOR');
        $cliForceColorEnv = getenv('CLICOLOR_FORCE');

        $cliColor = (int) ($cliColorEnv !== false ? $cliColorEnv : 1);
        $cliColorForce = (int) ($cliForceColorEnv !== false ? $cliForceColorEnv : 0);

        if (($cliColor === 0 || $this->getArgument('no-colors', false)) && $cliColorForce === 0) {
            $message = $this->clearMessage($message);
        }

        fwrite($this->stream, $message);

        return strlen($this->clearMessage($message));
    }

    public function writeLine(string $message): int
    {
        return $this->write($message . PHP_EOL);
    }

    public function password(string $message): string
    {
        $this->write(sprintf('%s: ', $message));

        return CliPrompt::hiddenPrompt(true);
    }

    public function prompt(string $message, string $default = ''): string
    {
        if ($default !== '') {
            $message .= "($default)";
        }


        if (function_exists('\readline')) {
            return trim((string) readline($this->normalizeText("{$message} "))) ?: $default;
        }

        $this->write("$message ");
        return trim((string) fgets(STDIN)) ?: $default;
    }

    public function confirm(
        string $message,
        string $default = ''
    ): bool {
        $response = $this->choice($message, ['y', 'n'], $default);

        return (strtolower($response) === 'y');
    }

    public function choice(
        string $message,
        array $options,
        string $default = ''
    ): string {
        $response = $this->prompt(
            $message . ' ['. implode(', ', $options) . ']',
            $default
        );

        if (!in_array(strtolower($response), array_map('strtolower', $options))) {
            $this->writeLine("%text:red%'{$response}' is not a valid selection");
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

    public function update(\SplSubject $subject)
    {
        $subject->display($this);
    }
}
