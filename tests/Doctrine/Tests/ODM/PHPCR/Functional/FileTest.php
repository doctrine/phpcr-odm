<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\File;

/**
 * @group functional
 */
class FileTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;
    private $childType;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\TestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreateFromFile()
    {
        $parent = new TestObj();
        $parent->file = new File();
        $parent->path = '/functional/filetest';
        $parent->file->setFileContentFromFilesystem('Doctrine/Tests/ODM/PHPCR/Functional/_files/foo.txt');

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('filetest')->hasNode('file'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
    }

    public function testCreateFromString()
    {
        $parent = new TestObj();
        $parent->file = new File();
        $parent->path = '/functional/filetest';
        $parent->file->setFileContent('Lorem ipsum dolor sit amet');

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('filetest')->hasNode('file'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
        $binary = $this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->getProperty('jcr:data')->getBinary();
        $content = $binary->read($binary->getSize());
        $this->assertEquals('Lorem ipsum dolor sit amet', $content);
    }

    public function testCreatedDate()
    {
        $this->markTestSkipped('This test fails due to a bug in UnitOfWork. Retest after merge.');
        $parent = new TestObj();
        $parent->file = new File();
        $parent->path = '/functional/filetest';
        $parent->file->setFileContentFromFilesystem('Doctrine/Tests/ODM/PHPCR/Functional/_files/foo.txt');

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $file = $this->dm->find('Doctrine\ODM\PHPCR\Document\File', '/functional/filetest/file');
        
        $this->assertNotNull($file);
        $this->assertNotNull($file->getCreated());
    }
}


/**
 * @phpcr:Document(alias="testObj")
 */
class TestObj
{
    /** @phpcr:Path */
    public $path;
    /** @phpcr:Node */
    public $node;
    /** @phpcr:String */
    public $name;
    /** @phpcr:Child */
    public $file;
}
