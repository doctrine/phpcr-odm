<?php

require_once __DIR__ . '/bootstrap.php';

$classLoader = new ClassLoader('Jackalope', __DIR__ . '/../lib/vendor/jackalope/src');
$classLoader->register();
