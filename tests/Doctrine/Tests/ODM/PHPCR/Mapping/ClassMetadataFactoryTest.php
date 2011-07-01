<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;

class ClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $session = $this->getMock('PHPCR\SessionInterface');
        $this->dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);
    }

    public function testNotMappedThrowsException()
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $this->setExpectedException('Doctrine\ODM\PHPCR\Mapping\MappingException');
        $cmf->getMetadataFor('unknown');
    }

    public function testGetMapping()
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');

        $cmf->setMetadataFor('stdClass', $cm);

        $this->assertTrue($cmf->hasMetadataFor('stdClass'));
        $this->assertSame($cm, $cmf->getMetadataFor('stdClass'));
    }

    public function testGetAllMetadata()
    {
        $driver = new \Doctrine\ODM\PHPCR\Mapping\Driver\PHPDriver(array(__DIR__));
        $this->dm->getConfiguration()->setMetadataDriverImpl($driver);

        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');
        $cmf->setMetadataFor('stdClass', $cm);

        $metadata = $cmf->getAllMetadata();

        $this->assertTrue(is_array($metadata));
    }

    public function testCacheDriver()
    {
        $this->markTestIncomplete('Test cache driver setting and handling.');
    }
}
