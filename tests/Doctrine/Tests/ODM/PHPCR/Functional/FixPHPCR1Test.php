<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\File;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class FixPHPCR1Test extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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
        $parent = new FixPHPCR1TestObj();
        $parent->id = '/functional/filetest';
        $this->dm->persist($parent);

        $parent->file = new File();
        $parent->file->setFileContentFromFilesystem(dirname(__FILE__) . '/_files/foo.txt');

        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('filetest')->hasNode('file'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
    }

}

/**
 * @PHPCRODM\Document()
 */
class FixPHPCR1TestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\Child(cascade="persist") */
    public $file;
}
