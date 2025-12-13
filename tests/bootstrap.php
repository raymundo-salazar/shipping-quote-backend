<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (class_exists(Dotenv::class)) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

// No sobreescribas si ya viene de phpunit.dist.xml
$_SERVER['APP_ENV'] ??= 'test';
$_ENV['APP_ENV'] ??= 'test';

$_SERVER['KERNEL_CLASS'] ??= 'App\Kernel';
