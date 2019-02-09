<?php
if (php_sapi_name() != "cli") {
    return;
}

include_once __DIR__ . '/error_handlers.php'; // Register pretty error handlers
include_once __DIR__ . '/signal_handlers.php'; // Register signal handlers

