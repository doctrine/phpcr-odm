<?php

use Doctrine\ODM\PHPCR\Event\PreFlushEventArgs;


class PreFlushEventArgsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->preFlushEventArgs = new PreFlushEventArgs($this->dm);
    }

    public function testGetDocumentManager()
    {
        $res = $this->preFlushEventArgs->getDocumentManager();
        $this->assertSame($this->dm, $res);
    }
}
