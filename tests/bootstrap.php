<?php

define('APP_TEST', true);

$debug = true;
if (isset($_ENV['APP_DEBUG'])) {
    $debug = (bool)$_ENV['APP_DEBUG'];
}
define('APP_DEBUG', $debug);

ini_set('error_log', '');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../main.php';
