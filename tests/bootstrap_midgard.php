<?php
require_once __DIR__ . '/bootstrap.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Midgard\PHPCR', __DIR__ . '/../lib/vendor/Midgard/PHPCR/src');
$classLoader->register();
