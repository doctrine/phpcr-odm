<?php

require_once __DIR__ . '/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\Tests', __DIR__ . '/../tests');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', __DIR__ . '/../lib/vendor/doctrine-common/lib');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$classLoader = new ClassLoader('Jackalope', __DIR__ . '/../lib/vendor/jackalope/src');
$classLoader->register();

$classLoader = new ClassLoader('PHPCR', __DIR__ . '/../lib/vendor/jackalope/lib/phpcr/src');
$classLoader->register();


$classLoader = new ClassLoader('Symfony', __DIR__ . '/../lib/vendor');
$classLoader->register();
