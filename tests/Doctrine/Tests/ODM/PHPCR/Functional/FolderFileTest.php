<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\File;
use Doctrine\ODM\PHPCR\Document\Folder;

use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;

/**
 * @group functional
 */
class FolderFileTest extends PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    const FILE_CONTENT = 'Lorem ipsum dolor sit amet';

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
        $file->setFileContent(self::FILE_CONTENT);

        $this->dm->persist($folder);
        $this->dm->persist($file);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFolderAndFile($this->node);
    }

    public function testCreateCascade()
    {
        $folder = new Folder();
        $folder->setId('/functional/folder');

        $file = new File();
        $file->setFileContent(self::FILE_CONTENT);
        $file->setNodename('file');
        $folder->addChild($file);

        $this->dm->persist($folder);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFolderAndFile($this->node);
    }

    /**
     * Assert that $node has a folder child that is an nt:folder and has a
     * child * called file that is an nt:file.
     *
     * @param NodeInterface $node
     */
    private function assertFolderAndFile(NodeInterface $node)
    {
        $this->assertTrue($node->hasNode('folder'));
        $folder = $node->getNode('folder');
        $this->assertTrue($folder->hasProperty('jcr:created'));
        $this->assertInstanceOf('\DateTime', $folder->getPropertyValue('jcr:created'));
        $this->assertTrue($folder->hasNode('file'));
        $file = $folder->getNode('file');
        $this->assertTrue($file->hasNode('jcr:content'));
        $resource = $file->getNode('jcr:content');
        $this->assertTrue($resource->hasProperty('jcr:lastModified'));
        $this->assertInstanceOf('\DateTime', $resource->getPropertyValue('jcr:lastModified'));
        $this->assertTrue($resource->hasProperty('jcr:data'));
        $binaryStream = $file->getNode('jcr:content')->getProperty('jcr:data')->getBinary();
        $this->assertNotNull($binaryStream, 'We got no content from the file');
        $content = stream_get_contents($binaryStream);
        $this->assertEquals(self::FILE_CONTENT, $content);
    }
}
