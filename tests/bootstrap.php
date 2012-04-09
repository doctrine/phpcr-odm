<?php

$autoload = require_once '../bootstrap.php';

// tests are not autoloaded the composer.json
$autoload->add('Doctrine\Tests', __DIR__);
