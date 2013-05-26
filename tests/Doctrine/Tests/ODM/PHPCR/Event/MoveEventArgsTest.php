<?php

use Doctrine\ODM\PHPCR\Event\MoveEventArgs;

class MoveEventArgsTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    private $dm;

    /** @var  MoveEventArgs */
    private $eventArgs;

    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->object = new \stdClass;

        $this->eventArgs = new MoveEventArgs(
            $this->object,
            $this->dm,
            'source/path',
            'target/path'
        );
    }

    public function testGetDocumentManager()
    {
        $res = $this->eventArgs->getObjectManager();
        $this->assertSame($this->dm, $res);
    }

    public function testGetDocument()
    {
        $res = $this->eventArgs->getObject();
        $this->assertSame($this->object, $res);
    }

    public function testGetSourcePath()
    {
        $path = $this->eventArgs->getSourcePath();
        $this->assertEquals('source/path', $path);
    }

    public function testGetTargetPath()
    {
        $path = $this->eventArgs->getTargetPath();
        $this->assertEquals('target/path', $path);
    }
}

