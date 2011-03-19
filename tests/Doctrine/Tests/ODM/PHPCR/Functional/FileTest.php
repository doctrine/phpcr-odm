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

    public function testCreate()
    {
        $parent = new TestObj();
        $parent->file = new File();
        $parent->file->setFileContent('Doctrine/Tests/ODM/PHPCR/Functional/_files/foo.txt');

        $this->dm->persist($parent, '/functional/filetest');
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('filetest')->hasNode('file'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
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

