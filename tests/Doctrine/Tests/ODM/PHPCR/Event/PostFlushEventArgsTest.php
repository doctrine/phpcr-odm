<?php

use Doctrine\ODM\PHPCR\Event\PostFlushEventArgs;


class PostFlushEventArgsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->postFlushEventArgs = new PostFlushEventArgs($this->dm);
    }

    public function testGetDocumentManager()
    {
        $res = $this->postFlushEventArgs->getDocumentManager();
        $this->assertSame($this->dm, $res);
    }
}
