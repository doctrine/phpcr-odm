<?php

require_once __DIR__ . '/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\DBAL', __DIR__.'/../lib/vendor/jackalope-doctrine-dbal/lib/vendor/doctrine-dbal/lib');
$classLoader->register();

$classLoader = new ClassLoader('Jackalope', __DIR__.'/../lib/vendor/jackalope-doctrine-dbal/lib/jackalope/src');
$classLoader->register();

//$classLoader = new ClassLoader('Jackalope', __DIR__.'/../lib/vendor/jackalope-doctrine-dbal/src');
//$classLoader->register();

require_once __DIR__.'/bootstrap.php';

require_once __DIR__.'/../lib/vendor/jackalope-doctrine-dbal/src/Jackalope/RepositoryFactoryDoctrineDBAL.php';
require_once __DIR__.'/../lib/vendor/jackalope-doctrine-dbal/src/Jackalope/Transport/DoctrineDBAL/Client.php';
require_once __DIR__.'/../lib/vendor/jackalope-doctrine-dbal/src/Jackalope/Transport/DoctrineDBAL/RepositorySchema.php';
require_once __DIR__.'/../lib/vendor/jackalope-doctrine-dbal/src/Jackalope/Transport/DoctrineDBAL/Query/QOMWalker.php';
