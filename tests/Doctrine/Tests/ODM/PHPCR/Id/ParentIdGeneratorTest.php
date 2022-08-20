<?php

namespace Doctrine\Tests\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\IdException;
use Doctrine\ODM\PHPCR\Id\ParentIdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\UnitOfWork;
use PHPUnit\Framework\TestCase;

class ParentIdGeneratorTest extends TestCase
{
    /**
     * @covers \Doctrine\ODM\PHPCR\Id\ParentIdGenerator::generate
     */
    public function testGenerate(): void
    {
        $id = '/moo';

        $generator = new ParentIdGenerator();
        $parent = new ParentDummy();
        $cm = new ParentClassMetadataProxy($parent, 'name', $id, new MockField($parent, '/miau'));
        $uow = $this->createMock(UnitOfWork::class);
        $uow
            ->expects($this->once())
            ->method('getDocumentId')
            ->with($this->equalTo($parent))
            ->willReturn('/miau');
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $this->assertEquals('/miau/name', $generator->generate(null, $cm, $dm));
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\ParentIdGenerator::generate
     */
    public function testGenerateNoParent(): void
    {
        $id = '/moo';

        $generator = new ParentIdGenerator();
        $cm = new ParentClassMetadataProxy(null, 'name', $id);
        $dm = $this->createMock(DocumentManager::class);

        $this->assertEquals($id, $generator->generate(null, $cm, $dm));
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\ParentIdGenerator::generate
     */
    public function testGenerateNoName(): void
    {
        $id = '/moo';

        $generator = new ParentIdGenerator();
        $cm = new ParentClassMetadataProxy(new ParentDummy(), '', $id);
        $dm = $this->createMock(DocumentManager::class);

        $this->assertEquals($id, $generator->generate(null, $cm, $dm));
    }

    public function testGenerateNoIdNoParentNoName(): void
    {
        $generator = new ParentIdGenerator();
        $cm = new ParentClassMetadataProxy(null, '', '');
        $dm = $this->createMock(DocumentManager::class);

        $this->expectException(IdException::class);
        $generator->generate(null, $cm, $dm);
    }

    public function testGenerateNoIdNoParent(): void
    {
        $generator = new ParentIdGenerator();
        $cm = new ParentClassMetadataProxy(null, 'name', '');
        $dm = $this->createMock(DocumentManager::class);

        $this->expectException(IdException::class);
        $generator->generate(null, $cm, $dm);
    }

    public function testGenerateNoIdNoName(): void
    {
        $generator = new ParentIdGenerator();
        $cm = new ParentClassMetadataProxy(new ParentDummy(), '', '');
        $dm = $this->createMock(DocumentManager::class);

        $this->expectException(IdException::class);
        $generator->generate(null, $cm, $dm);
    }

    public function testGenerateNoParentId(): void
    {
        $generator = new ParentIdGenerator();
        $parent = new ParentDummy();
        $cm = new ParentClassMetadataProxy($parent, 'name', '', new MockField($parent, '/miau'));
        $uow = $this->createMock(UnitOfWork::class);
        $uow
            ->expects($this->once())
            ->method('getDocumentId')
            ->with($this->equalTo($parent))
            ->willReturn('');
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->expectException(IdException::class);
        $generator->generate(null, $cm, $dm);
    }
}

class ParentDummy
{
}
class ParentClassMetadataProxy extends ClassMetadata
{
    public $parentMapping = 'parent';

    public $nodename = 'nodename';

    public $identifier = 'id';

    public $reflFields;

    protected $_parent;

    protected $_nodename;

    protected $_id;

    public function __construct($parent, $nodename, $identifier, $mockField = null)
    {
        $this->_parent = $parent;
        $this->_nodename = $nodename;
        $this->_identifier = $identifier;

        $this->reflFields = [$this->identifier => $mockField];
    }

    public function getFieldValue($document, $field)
    {
        switch ($field) {
            case $this->parentMapping:
                return $this->_parent;
            case $this->nodename:
                return $this->_nodename;
            case $this->identifier:
                return $this->_identifier;
        }
    }
}

class MockField
{
    private $p;

    private $id;

    public function __construct($parent, $id)
    {
        $this->p = $parent;
        $this->id = $id;
    }

    public function getValue($parent)
    {
        if (!$this->p == $parent) {
            throw new \Exception('Wrong parent passed in getValue');
        }

        return $this->id;
    }
}
