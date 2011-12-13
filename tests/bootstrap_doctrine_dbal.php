<?php

require_once __DIR__ . '/bootstrap.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\DBAL', __DIR__ . '/../../doctrine-dbal/lib');
$classLoader->register();
