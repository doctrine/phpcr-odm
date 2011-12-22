<?php

require_once __DIR__ . '/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\DBAL', __DIR__ . '/../lib/vendor/jackalope/lib/vendor/doctrine-dbal/lib');
$classLoader->register();

$classLoader = new ClassLoader('Jackalope', __DIR__ . '/../lib/vendor/jackalope/src');
$classLoader->register();

require_once __DIR__ . '/bootstrap.php';
