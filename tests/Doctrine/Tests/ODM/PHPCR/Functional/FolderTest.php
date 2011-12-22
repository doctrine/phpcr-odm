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

    public function testCreateManual()
    {
        $folder = new Folder();
        $folder->setId('/functional/folder');

        $file = new File();
        $file->setId('/functional/folder/file');
        $file->setFileContent('Lorem ipsum dolor sit amet');

        $this->dm->persist($folder);
        $this->dm->persist($file);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->hasNode('folder'));
        $this->assertTrue($this->node->getNode('folder')->hasNode('file'));
        $this->assertTrue($this->node->getNode('folder')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('folder')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
        $binaryStream = $this->node->getNode('folder')->getNode('file')->getNode('jcr:content')->getProperty('jcr:data')->getBinary();
        $this->assertNotNull($binaryStream, 'Ensure that we got a stream from the file');
        $content = stream_get_contents($binaryStream);
        $this->assertEquals('Lorem ipsum dolor sit amet', $content);
    }

    public function testCreateCascade()
    {
        $folder = new Folder();
        $folder->setId('/functional/folder');

        $file = new File();
        $file->setFileContent('Lorem ipsum dolor sit amet');
        $file->setNodename('file');
        $folder->addChild($file);

        $this->dm->persist($folder);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->hasNode('folder'));
        $this->assertTrue($this->node->getNode('folder')->hasNode('file'));
        $this->assertTrue($this->node->getNode('folder')->getNode('file')->hasNode('jcr:content'));
        $this->assertTrue($this->node->getNode('folder')->getNode('file')->getNode('jcr:content')->hasProperty('jcr:data'));
        $binaryStream = $this->node->getNode('folder')->getNode('file')->getNode('jcr:content')->getProperty('jcr:data')->getBinary();
        $content = stream_get_contents($binaryStream);
        $this->assertEquals('Lorem ipsum dolor sit amet', $content);
    }
}
