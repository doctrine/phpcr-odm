<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\ODM\PHPCR\Mapping\Model\MixinMappingObject;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

/**
 * @group functional
 */
class MixinTest extends PHPCRFunctionalTestCase
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
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Mapping\Model\MixinMappingObject';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testMixin()
    {
        $mixin = new MixinMappingObject();
        $mixin->id = '/functional/mixin';

        $this->dm->persist($mixin);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('mixin')->hasProperty('jcr:lastModified'));
        $this->assertTrue($this->node->getNode('mixin')->hasProperty('jcr:lastModifiedBy'));
        $this->assertNotNull($this->node->getNode('mixin')->getProperty('jcr:lastModified'));
        $this->assertNotNull($this->node->getNode('mixin')->getProperty('jcr:lastModifiedBy'));
        $this->assertNotNull($this->node->getNode('mixin')->getProperty('jcr:lastModifiedBy')->getString(), 'admin');
    }
}