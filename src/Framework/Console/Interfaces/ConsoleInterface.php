<?php declare(strict_types=1);

namespace Onion\Framework\Console\Interfaces;

interface ConsoleInterface
{
    const COLOR_TERMINATOR = "\033[0m";
    const TEXT_COLORS = [
        'none'  =>          '',
        'white' =>          "\33[1;37m",
        'black' =>          "\33[30m",
        'blue'  =>          "\33[34m",
        'green' =>          "\33[32m",
        'cyan'  =>          "\33[36m",
        'red'   =>          "\33[31m",
        'yellow'=>          "\33[33m",
        'purple' =>         "\33[35m",
        'brown' =>          "\33[33m",
        'gray' =>           "\33[30m",
        'bold-blue'  =>    "\33[1;34m",
        'bold-green' =>    "\33[1;32m",
        'bold-cyan'  =>    "\33[1;36m",
        'bold-red'   =>    "\33[1;31m",
        'bold-yellow'=>    "\33[1;33m",
        'bold-purple' =>   "\33[1;35m",
        'bold-gray' =>     "\33[1;37m",
        'dark-blue'  =>    "\33[2;34m",
        'dark-green' =>    "\33[2;32m",
        'dark-cyan'  =>    "\33[2;36m",
        'dark-red'   =>    "\33[2;31m",
        'dark-yellow'=>    "\33[2;33m",
        'dark-purple' =>   "\33[2;35m",
        'dark-gray' =>     "\33[2;37m",
        'italic-blue'  =>    "\33[3;34m",
        'italic-green' =>    "\33[3;32m",
        'italic-cyan'  =>    "\33[3;36m",
        'italic-red'   =>    "\33[3;31m",
        'italic-yellow'=>    "\33[3;33m",
        'italic-purple' =>   "\33[3;35m",
        'italic-gray' =>     "\33[3;37m",
        'underline-blue'  =>    "\33[4;34m",
        'underline-green' =>    "\33[4;32m",
        'underline-cyan'  =>    "\33[4;36m",
        'underline-red'   =>    "\33[4;31m",
        'underline-yellow'=>    "\33[4;33m",
        'underline-purple' =>   "\33[4;35m",
        'underline-gray' =>     "\33[4;37m",
    ];

    const BACKGROUND_COLORS = [
        'none'  =>          '',
        'white' =>          "\33[47m",
        'black' =>          "\33[40m",
        'blue'  =>          "\33[44m",
        'green' =>          "\33[42m",
        'cyan'  =>          "\33[46m",
        'red'   =>          "\33[1;41m",
        'yellow'=>          "\33[1;43m",
        'purple' =>         "\33[45m",
        'brown' =>          "\33[43m",
        'gray' =>           "\33[1;40m"
    ];

    const PROMPT_YES = 'y';
    const PROMPT_NO  = 'n';

    public function withArgument(string $argument, $value): ConsoleInterface;
    public function withoutArgument(string $argument): ConsoleInterface;
    public function hasArgument(string $argument): bool;
    public function getArgument(string $argument, $default = null);

    public function block(
        string $message,
        int $width,
        string $backgroundColor = 'none',
        string $textColor = 'none'
    ): int;
    public function write(string $message, string $textColor = 'none', string $backgroundColor = 'none'): int;
    public function writeLine(string $message, string $textColor = 'none', string $backgroundColor = 'none'): int;
    public function prompt(string $message, bool $protected = false, string $textColor = 'none', string $backgroundColor = 'none'): string;
    public function choice(
        string $message,
        string $default = 'n',
        string $truth = 'y',
        string $textColor = 'none',
        string $backgroundColor = 'none'
    ): bool;
}
