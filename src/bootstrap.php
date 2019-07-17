<?php
use Onion\Framework\Console\Console;
use Onion\Framework\Loop\Descriptor;

if (php_sapi_name() != "cli") {
    return;
}

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

    $console = new Console(fopen($stream, 'wb'));
    $console->writeLine("%text:white%[ %text:$color%$type%end%%text:white% ] - {$message} - {$file}@{$line}");
    $console->writeLine('');
}, E_ALL);

set_exception_handler(function (\Throwable $ex) {
    $console = new Console(fopen('php://stderr', 'wb'));
    $console->writeLine("%bg:red%%text:yellow%[ %text:red%ERROR%end%%text:yellow% ] - {$ex->getMessage()} - {$ex->getFile()}@{$ex->getLine()}");
    $console->writeLine('');
    exit($ex->getCode() ?: 1);
});
