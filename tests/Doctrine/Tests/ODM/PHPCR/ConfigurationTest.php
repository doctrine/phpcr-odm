<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\PHPCRException;

/**
 * @group unit
 */
class ConfigurationTest extends PHPCRTestCase
{
    /**
     * @covers \Doctrine\ODM\PHPCR\Configuration::addDocumentNamespace
     * @covers \Doctrine\ODM\PHPCR\Configuration::getDocumentNamespace
     * @covers \Doctrine\ODM\PHPCR\Configuration::setDocumentNamespaces
     */
    public function testDocumentNamespace()
    {
        $config = new Configuration();

        $config->addDocumentNamespace('foo', 'Documents\Bar');
        $this->assertEquals('Documents\Bar', $config->getDocumentNamespace('foo'));

        $config = new Configuration();

        $config->setDocumentNamespaces(array('foo' => 'Documents\Bar'));
        $this->assertEquals('Documents\Bar', $config->getDocumentNamespace('foo'));

        $this->expectException(PHPCRException::class);
        $config->getDocumentNamespace('bar');
    }
}
