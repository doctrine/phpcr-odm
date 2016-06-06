<?php

// The benchmarks are instances of the PHPUnit functional test case.
// Here we read the PHPUnit configuration file and set the $GLOBALS as
// expected.

// TRANSPORT (jackalope, doctrine_dbal, etc) is set by travis.
$transport = getenv('TRANSPORT') ?: null;

$phpunitFile = $transport ? sprintf('phpunit_%s.xml.dist', $transport) : 'phpunit.xml';
$phpunitPath = __DIR__ . '/../tests/' . $phpunitFile;

if (!file_exists($phpunitPath)) {
    throw new \InvalidArgumentException(sprintf(
        'Could not find a PHPUnit configuration file at "%s"',
        $phpunitPath
    ));
}

$dom = new \DOMDocument(1.0);
$dom->load($phpunitPath);
$xpath = new \DOMXpath($dom);

foreach ($xpath->query('//php/var') as $varEl) {
    $GLOBALS[$varEl->getAttribute('name')] = $varEl->getAttribute('value');
}

require(__DIR__ . '/../tests/bootstrap.php');
