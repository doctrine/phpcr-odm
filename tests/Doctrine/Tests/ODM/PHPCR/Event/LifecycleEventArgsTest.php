<?php

use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;

class LifecycleEventArgsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->object = new \stdClass;

        $this->eventArgs = new LifecycleEventArgs(
            $this->object,
            $this->dm
        );
    }

    public function testGetDocumentManager()
    {
        $res = $this->eventArgs->getDocumentManager();
        $this->assertSame($this->dm, $res);
    }

    public function testGetDocument()
    {
        $res = $this->eventArgs->getDocument();
        $this->assertSame($this->object, $res);
    }
}

