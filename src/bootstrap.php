<?php

use Onion\Framework\Console\Console;
use Onion\Framework\Console\Buffer;

set_error_handler(function ($level, $message, $file, $line) {
    $stream = 'php://stdout';
    switch ($level) {
        case E_USER_WARNING:
        case E_WARNING:
            $stream = 'php://stderr';
            $type = 'WARNING';
            $color = 'yellow';
            break;
        case E_USER_NOTICE:
        case E_NOTICE:
        case E_USER_DEPRECATED:
            $type = 'NOTICE';
            $color = 'cyan';
            break;
        default:
            $stream = 'php://stderr';
            $type = 'ERROR';
            $color = 'red';
            break;
    }

    $console = new Console(new Buffer($stream));
    $console->writeLine("%text:white%[ %text:$color%$type%end%%text:white% ] - {$message}");
    $console->writeLine('');
}, E_ALL);

set_exception_handler(function (\Throwable $ex) {
    $console = new Console(new Buffer('php://stderr'));
    $console->writeLine('%text:yellow%[ %text:red%ERROR%end%%text:yellow% ] - ' . $ex->getMessage());
    $console->writeLine('');
    exit($ex->getCode() ?: 1);
});
