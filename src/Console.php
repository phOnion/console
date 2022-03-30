<?php

declare(strict_types=1);

namespace Onion\Framework\Console;

use DOMDocument;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Seld\CliPrompt\CliPrompt;

class Console implements ConsoleInterface
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
            return rtrim((string) readline($this->normalizeText("{$message} "))) ?: $default;
        }

        $this->write("$message ");
        return rtrim((string) fgets(STDIN)) ?: $default;
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
            $message . ' [' . implode(', ', $options) . ']',
            $default
        );

        if (!in_array(strtolower($response), array_map('strtolower', $options))) {
            trigger_error("'{$response}' is not valid for selection", E_USER_ERROR);

            $response = $this->choice($message, $options, $default);
        }

        return $response;
    }

    private function parse(\DOMNode $doc, string $color = '', string $bg = ''): string
    {
        $message = '';

        for ($i = 0; $i < $doc->childNodes->length; $i++) {
            $child = $doc->childNodes->item($i);
            $attributes = [];
            if ($child->hasAttributes()) {
                foreach ($child->attributes as $attribute) {
                    /** @var \DOMAttr $attribute */
                    $attributes[$attribute->name] = $attribute->value;
                }
            }

            $key = '';
            if (isset($attributes['decoration'])) {
                $key .= $attributes['decoration'] . '-';
            }

            if (isset($attributes['light'])) {
                $key .= 'light-';
            }

            if (isset($attributes['text'])) {
                $key .= $attributes['text'];
            }

            switch ($child->nodeType) {
                case XML_TEXT_NODE:
                    $message .= sprintf(
                        '%s%s%s',
                        $color,
                        $bg,
                        $child->nodeValue,
                    );
                    break;
                case XML_ELEMENT_NODE:
                    $message .= match ($child->nodeName) {
                        'color' => sprintf('%s%s', $this->parse($child, self::TEXT_COLORS[$key] ?? '', self::BACKGROUND_COLORS[$attributes['bg'] ?? 'none']), self::COLOR_TERMINATOR),
                        default => (new DOMDocument())->saveHTML($child),
                    };
                    break;
            }
        }

        return $message;
    }
    public function normalizeText(string $message): string
    {
        $doc = new DOMDocument();

        $doc->loadXML('<root>' . str_replace(
            ['<', '&lt;color', '&lt;/color'],
            ['&lt;', '<color', '</color'],
            // ! Handle unsupported characters inside user-provided text,
            // ! prevents issues in handling of color tags as XML
            preg_replace_callback(
                '/(\p{C})/u',
                fn (array $chars) => match ($chars[0]) {
                    "\t" => "\t",
                    "\n" => "\n",
                    "\r" => "\r",
                    default => '',
                },
                $message,
            )
        ) . '</root>', LIBXML_PARSEHUGE | LIBXML_COMPACT);

        return $this->parse($doc->childNodes->item(0));
    }

    public function copy(mixed $resource): int
    {
        $size = 0;
        fseek($resource, 0);
        while (!feof($resource)) {
            $r = (string) fread($resource, 1024);
            $size += $this->write($r);
        }

        return $size;
    }

    private function clearMessage(string $message): string
    {
        return preg_replace("#(\033\[[0-9;]*m)#i", '', $message);
    }
}
