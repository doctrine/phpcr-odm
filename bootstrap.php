<?php

$vendorDir = __DIR__.'/vendor';

$file = $vendorDir.'/.composer/autoload.php';
if (file_exists($file)) {
    $autoload = require_once $file;
} else {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$autoload->add('Doctrine\ODM\PHPCR', __DIR__.'/lib');
$autoload->add('Doctrine\Tests', __DIR__.'/tests');

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerLoader(function($class) use ($autoload) {
    $autoload->loadClass($class);
    return class_exists($class, false);
});
AnnotationRegistry::registerFile(__DIR__.'/lib/Doctrine/ODM/PHPCR/Mapping/Annotations/DoctrineAnnotations.php');
