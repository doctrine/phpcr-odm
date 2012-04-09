<?php

$autoload = require_once __DIR__.'/../bootstrap.php';

// tests are not autoloaded the composer.json
$autoload->add('Doctrine\Tests', __DIR__);
