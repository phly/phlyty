<?php
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new DomainException('You do not appear to have run "php composer.phar install"; autoloading is disabled, and tests cannot be run.');
}
include_once __DIR__ . '/../vendor/autoload.php';
spl_autoload_register(function ($class) {
    if (!preg_match('/^PhlytyTest\\\\/', $class)) {
        return false;
    }
    $filename = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (!file_exists($filename)) {
        return false;
    }
    return include_once($filename);
}, false, true);
