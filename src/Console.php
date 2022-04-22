<?php

declare(strict_types=1);

namespace Onion\Framework\Console;

use DOMDocument;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Types\Confirmation;
use Seld\CliPrompt\CliPrompt;

class Console implements ConsoleInterface
{
    private const COLOR_TERMINATOR = "\33[0m";
    private const TEXT_COLORS = [
        'none' => '',
        'white' => "\33[0;37m",
        'black' => "\33[0;30m",
        'blue' => "\33[0;34m",
        'green' => "\33[0;32m",
        'cyan' => "\33[0;36m",
        'red' => "\33[0;31m",
        'yellow' => "\33[0;33m",
        'purple' => "\33[0;35m",
        'bold-white' => "\33[1;37m",
        'bold-blue' => "\33[1;34m",
        'bold-green' => "\33[1;32m",
        'bold-cyan' => "\33[1;36m",
        'bold-red' => "\33[1;31m",
        'bold-yellow' => "\33[1;33m",
        'bold-purple' => "\33[1;35m",
        'italic-white' => "\33[3;37m",
        'italic-blue' => "\33[3;34m",
        'italic-green' => "\33[3;32m",
        'italic-cyan' => "\33[3;36m",
        'italic-red' => "\33[3;31m",
        'italic-yellow' => "\33[3;33m",
        'italic-purple' => "\33[3;35m",
        'underline-white' => "\33[4;37m",
        'underline-blue' => "\33[4;34m",
        'underline-green' => "\33[4;32m",
        'underline-cyan' => "\33[4;36m",
        'underline-red' => "\33[4;31m",
        'underline-yellow' => "\33[4;33m",
        'underline-purple' => "\33[4;35m",
        'light-blue' => "\33[0;94m",
        'light-green' => "\33[0;92m",
        'light-cyan' => "\33[0;96m",
        'light-red' => "\33[0;91m",
        'light-yellow' => "\33[0;93m",
        'light-purple' => "\33[0;95m",
        'bold-light-blue' => "\33[1;94m",
        'bold-light-green' => "\33[1;92m",
        'bold-light-cyan' => "\33[1;96m",
        'bold-light-red' => "\33[1;91m",
        'bold-light-yellow' => "\33[1;93m",
        'bold-light-purple' => "\33[1;95m",
        'italic-light-blue' => "\33[3;94m",
        'italic-light-green' => "\33[3;92m",
        'italic-light-cyan' => "\33[3;96m",
        'italic-light-red' => "\33[3;91m",
        'italic-light-yellow' => "\33[3;93m",
        'italic-light-purple' => "\33[3;95m",
        'underline-light-blue' => "\33[4;94m",
        'underline-light-green' => "\33[4;92m",
        'underline-light-cyan' => "\33[4;96m",
        'underline-light-red' => "\33[4;91m",
        'underline-light-yellow' => "\33[4;93m",
        'underline-light-purple' => "\33[4;95m",
    ];

    private const BACKGROUND_COLORS = [
        'none'  =>          '',
        'white' =>          "\33[47m",
        'black' =>          "\33[40m",
        'blue'  =>          "\33[44m",
        'green' =>          "\33[42m",
        'cyan'  =>          "\33[46m",
        'red'   =>          "\33[1;41m",
        'yellow' =>          "\33[1;43m",
        'purple' =>         "\33[45m",
        'brown' =>          "\33[43m",
    ];

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

    public function getArgument(string $argument, mixed $default = null): mixed
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
        ?Confirmation $default = null
    ): bool {
        $response = $this->choice($message, [array_map(fn (Confirmation $case) => $case->value, Confirmation::cases())], $default?->value ?? '');

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
