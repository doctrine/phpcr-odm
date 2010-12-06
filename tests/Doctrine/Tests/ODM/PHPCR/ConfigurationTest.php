<?php

namespace Doctrine\Tests\ODM\PHPCR;

class ConfigurationTest extends PHPCRTestCase
{
    public function testDocumentNamespace()
    {
        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $config->addDocumentNamespace('foo', 'Documents\Bar');
        $this->assertEquals('Documents\Bar', $config->getDocumentNamespace('foo'));

        $config->setDocumentNamespaces(array('foo' => 'Documents\Bar'));
        $this->assertEquals('Documents\Bar', $config->getDocumentNamespace('foo'));

        $this->setExpectedException('Doctrine\ODM\PHPCR\PHPCRException');
        $config->getDocumentNamespace('bar');
    }
}