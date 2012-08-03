<?php
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new DomainException('You do not appear to have run "php composer.phar install"; autoloading is disabled, and tests cannot be run.');
}
include_once __DIR__ . '/../vendor/autoload.php';
