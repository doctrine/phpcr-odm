<?php

$file = __DIR__.'/../vendor/autoload.php';
if (file_exists($file)) {
    $autoload = require $file;
} else {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$files = array_filter([
    __DIR__.'/../vendor/symfony/symfony/src/Symfony/Bridge/PhpUnit/bootstrap.php',
    __DIR__.'/../vendor/symfony/phpunit-bridge/bootstrap.php',
], 'file_exists');
if ($files) {
    require_once current($files);
}

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerLoader([$autoload, 'loadClass']);

// tests are not autoloaded the composer.json
$autoload->add('Doctrine\Tests', __DIR__);
