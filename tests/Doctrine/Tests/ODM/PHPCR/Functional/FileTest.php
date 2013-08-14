<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\File;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class FileTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var string
     */
    private $type;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\FileTestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreateFromFile()
    {
        $parent = new FileTestObj();
        $parent->file = new File();
        $parent->id = '/functional/filetest';
        $parent->file->setFileContentFromFilesystem(dirname(__FILE__) . '/_files/foo.txt');

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('filetest')->hasNode('file'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
    }

    public function testCreateFromString()
    {
        $parent = new FileTestObj();
        $parent->file = new File();
        $parent->id = '/functional/filetest';
        $parent->file->setFileContent('Lorem ipsum dolor sit amet');

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('filetest')->hasNode('file'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
        $binaryStream = $this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->getProperty('jcr:data')->getBinary();
        $this->assertNotNull($binaryStream, 'Ensure that we got a stream from the file');
        $content = stream_get_contents($binaryStream);
        $this->assertEquals('Lorem ipsum dolor sit amet', $content);
    }

    public function testCreatedDate()
    {
        $parent = new FileTestObj();
        $parent->file = new File();
        $parent->id = '/functional/filetest';
        $parent->file->setFileContentFromFilesystem(dirname(__FILE__) .'/_files/foo.txt');

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $file = $this->dm->find('Doctrine\ODM\PHPCR\Document\File', '/functional/filetest/file');

        $this->assertNotNull($file);
        $this->assertNotNull($file->getCreated());
    }
}

/**
 * @PHPCRODM\Document()
 */
class FileTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String(nullable=true) */
    public $name;
    /**
     * @var File
     * @PHPCRODM\Child
     */
    public $file;
}
