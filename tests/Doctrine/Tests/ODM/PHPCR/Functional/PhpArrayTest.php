<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use PHPCR\PropertyType;
use PHPCR\Util\UUIDHelper;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\Models\CMS\CmsBlock;

/**
 * @group functional
 */
class PhpArrayTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function providePersist()
    {
        return array(
            array(
                array(
                    'rows' => 3,
                    'title' => 'Foobar',
                ),
            )
        );
    }

    /**
     * @dataProvider providePersist
     */
    public function testPersist($config)
    {
        $block = new CmsBlock();
        $block->id = '/functional/test';
        $block->config = $config;
        $this->dm->persist($block);
        $this->dm->flush();
        $this->dm->clear();

        $block = $this->dm->find(null, '/functional/test');
        $this->assertSame(
            $config,
            $block->config
        );
    }
}

