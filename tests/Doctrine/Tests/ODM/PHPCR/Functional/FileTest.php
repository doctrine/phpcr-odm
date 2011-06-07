<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\File;
use Doctrine\ODM\PHPCR\Mapping\Annotations as ODM;

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
 * @ODM\Document(alias="testObj")
 */
class FileTestObj
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Node */
    public $node;
    /** @ODM\String */
    public $name;
    /** @ODM\Child */
    public $file;
}
