<?php

namespace Doctrine\Tests\ODM\PHPCR;

/**
 * @group unit
 */
class ConfigurationTest extends PHPCRTestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\Configuration::addDocumentNamespace
     * @covers Doctrine\ODM\PHPCR\Configuration::getDocumentNamespace
     * @covers Doctrine\ODM\PHPCR\Configuration::setDocumentNamespaces
     */
    public function testDocumentNamespace()
    {
        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $config->addDocumentNamespace('foo', 'Documents\Bar');
        $this->assertEquals('Documents\Bar', $config->getDocumentNamespace('foo'));

        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $config->setDocumentNamespaces(array('foo' => 'Documents\Bar'));
        $this->assertEquals('Documents\Bar', $config->getDocumentNamespace('foo'));

        $this->setExpectedException('Doctrine\ODM\PHPCR\PHPCRException');
        $config->getDocumentNamespace('bar');
    }
}