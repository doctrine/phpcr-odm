<?php

$dom = new \DOMDocument(1.0);
$dom->load(__DIR__ . '/../tests/phpunit.xml');
$xpath = new \DOMXpath($dom);

foreach ($xpath->query('//php/var') as $varEl) {
    $GLOBALS[$varEl->getAttribute('name')] = $varEl->getAttribute('value');
}

require(__DIR__ . '/../tests/bootstrap.php');
