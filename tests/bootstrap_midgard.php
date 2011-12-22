<?php
if (gc_enabled()) {
    echo "Disabling Zend Garbage Collection to prevent segfaults, see:\n";
    echo "  https://bugs.php.net/bug.php?id=51091\n";
    echo "  https://github.com/midgardproject/midgard-php5/issues/50\n";
    gc_disable(); 
}

require_once __DIR__ . '/bootstrap.php';

use Doctrine\Common\ClassLoader;

$classLoader = new ClassLoader('Midgard\PHPCR', __DIR__ . '/../lib/vendor/Midgard/PHPCR/src');
$classLoader->register();
