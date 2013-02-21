<?php

use Doctrine\ODM\PHPCR\Event\ManagerEventArgs;


class ManagerEventArgsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->postFlushEventArgs = new ManagerEventArgs($this->dm);
    }

    public function testGetDocumentManager()
    {
        $res = $this->postFlushEventArgs->getDocumentManager();
        $this->assertSame($this->dm, $res);
    }

    public function testGetObjectManager()
    {
        $res = $this->postFlushEventArgs->getObjectManager();
        $this->assertSame($this->dm, $res);
    }
}
