<?php

use Doctrine\ODM\PHPCR\Event\LoadClassMetadataEventArgs;


class LoadClassMetadataEventArgsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->classMeta = $this->getMockBuilder('Doctrine\ODM\PHPCR\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->loadClassMetadataEventArgs = new LoadClassMetadataEventArgs(
            $this->classMeta,
            $this->dm
        );
    }

    public function testGetDocumentManager()
    {
        $res = $this->loadClassMetadataEventArgs->getDocumentManager();
        $this->assertSame($this->dm, $res);
    }

    public function testGetClassMetadata()
    {
        $res = $this->loadClassMetadataEventArgs->getClassMetadata();
        $this->assertSame($this->classMeta, $res);
    }
}

