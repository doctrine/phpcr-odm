<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\File;
use Doctrine\ODM\PHPCR\Document\Folder;

/**
 * @group functional
 */
class FolderTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreate()
    {
        $folder = new Folder();
        $folder->setPath('/functional/folder');

        $file = new File();
        $file->setPath('/functional/folder/file');
        $file->setFileContent('Lorem ipsum dolor sit amet');

        $this->dm->persist($folder);
        $this->dm->persist($file);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->hasNode('folder'));
        $this->assertTrue($this->node->getNode('folder')->hasNode('file'));
        $this->assertTrue($this->node->getNode('folder')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('folder')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
        $binary = $this->node->getNode('folder')->getNode('file')->getNode('jcr:content')->getProperty('jcr:data')->getBinary();
        $content = $binary->read($binary->getSize());
        $this->assertEquals('Lorem ipsum dolor sit amet', $content);
    }
}
