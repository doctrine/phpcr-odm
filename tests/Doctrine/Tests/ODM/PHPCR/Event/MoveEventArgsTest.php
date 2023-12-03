<?php

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Event\MoveEventArgs;
use PHPUnit\Framework\TestCase;

class MoveEventArgsTest extends TestCase
{
    private $object;

    private $dm;

    /**
     * @var MoveEventArgs
     */
    private $eventArgs;

    public function setUp(): void
    {
        $this->dm = $this->createMock(DocumentManager::class);
        $this->object = new stdClass();

        $this->eventArgs = new MoveEventArgs(
            $this->object,
            $this->dm,
            'source/path',
            'target/path'
        );
    }

    public function testGetDocumentManager(): void
    {
        $res = $this->eventArgs->getObjectManager();
        $this->assertSame($this->dm, $res);
    }

    public function testGetDocument(): void
    {
        $res = $this->eventArgs->getObject();
        $this->assertSame($this->object, $res);
    }

    public function testGetSourcePath(): void
    {
        $path = $this->eventArgs->getSourcePath();
        $this->assertEquals('source/path', $path);
    }

    public function testGetTargetPath(): void
    {
        $path = $this->eventArgs->getTargetPath();
        $this->assertEquals('target/path', $path);
    }
}
