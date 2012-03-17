<?php

$vendorDir = __DIR__.'/vendor';

spl_autoload_register(function($class)
{
    if (0 === strpos($class, 'Doctrine\ODM\PHPCR')) {
        $path = __DIR__.'/lib/'.implode('/', explode('\\', $class)).'.php';
        if (!stream_resolve_include_path($path)) {
            return false;
        }
        require_once $path;
        return true;
    }
    if (0 === strpos($class, 'Doctrine\Tests')) {
        $path = __DIR__.'/tests/'.implode('/', explode('\\', $class)).'.php';
        if (!stream_resolve_include_path($path)) {
            return false;
        }
        require_once $path;
        return true;
    }
});

$file = $vendorDir.'/.composer/autoload.php';
if (file_exists($file)) {
    $autoload = require_once $file;
} else {
    throw new RuntimeException('Install dependencies to run test suite.');
}

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerLoader(function($class) use ($autoload) {
    $autoload->loadClass($class);
    return class_exists($class, false);
});
AnnotationRegistry::registerFile(__DIR__.'/lib/Doctrine/ODM/PHPCR/Mapping/Annotations/DoctrineAnnotations.php');
