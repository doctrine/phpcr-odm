<?php

require_once __DIR__ . '/../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Doctrine\DBAL', __DIR__.'/../lib/vendor/jackalope-jackrabbit/lib/vendor/jackrabbit/lib');
$classLoader->register();

$classLoader = new ClassLoader('Jackalope', __DIR__.'/../lib/vendor/jackalope-jackrabbit/lib/jackalope/src');
$classLoader->register();

//$classLoader = new ClassLoader('Jackalope', __DIR__.'/../lib/vendor/jackalope-jackrabbit/src');
//$classLoader->register();

require_once __DIR__.'/bootstrap.php';

require_once __DIR__.'/../lib/vendor/jackalope-jackrabbit/src/Jackalope/RepositoryFactoryJackrabbit.php';
require_once __DIR__.'/../lib/vendor/jackalope-jackrabbit/src/Jackalope/Transport/Jackrabbit/Client.php';
require_once __DIR__.'/../lib/vendor/jackalope-jackrabbit/src/Jackalope/Transport/Jackrabbit/curl.php';
require_once __DIR__.'/../lib/vendor/jackalope-jackrabbit/src/Jackalope/Transport/Jackrabbit/HTTPErrorException.php';
require_once __DIR__.'/../lib/vendor/jackalope-jackrabbit/src/Jackalope/Transport/Jackrabbit/Request.php';
