<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\File;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;

/**
 * @group functional
 */
class FixPHPCR1Test extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreateFromFile(): void
    {
        $parent = new FixPHPCR1TestObj();
        $parent->id = '/functional/filetest';
        $this->dm->persist($parent);

        $parent->file = new File();
        $parent->file->setFileContentFromFilesystem(__DIR__.'/_files/foo.txt');

        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('filetest')->hasNode('file'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('filetest')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
    }
}

#[PHPCR\Document]
class FixPHPCR1TestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Child(cascade: 'persist')]
    public $file;
}
