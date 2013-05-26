<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\ODM\PHPCR\Mapping\Model\MixinMappingObject;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

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
    }

    public function testProtectedPropertyIsCreatedAndNotChanged()
    {
        $test = new TestObject();
        $test->id = '/functional/protected';
        $test->changeMe = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $created = $this->node->getNode('protected')->getProperty('jcr:created')->getDate();

        $test = $this->dm->find(null, '/functional/protected');
        $test->changeMe = 'changed';

        $this->dm->flush();


        $this->assertEquals($created, $this->node->getNode('protected')->getProperty('jcr:created')->getDate());
        $this->assertEquals('changed', $this->node->getNode('protected')->getProperty('change_me')->getString());
    }

    /**
     * @expectedException \PHPCR\NodeType\ConstraintViolationException
     */
    public function testChangingProtectedPropertyThrowsException()
    {
        $test = new TestObject();
        $test->id = '/functional/protected';
        $test->changeMe = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(null, '/functional/protected');
        $test->changeMe = 'changed';
        $test->created = new \DateTime();

        $this->dm->flush();

        $this->assertEquals('changed', $this->node->getNode('protected')->getProperty('change_me')->getString());
    }

    /**
     * @expectedException \PHPCR\NodeType\ConstraintViolationException
     */
    public function testChangingProtectedPropertyToNullThrowsException()
    {
        $test = new TestObject();
        $test->id = '/functional/protected';
        $test->changeMe = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(null, '/functional/protected');
        $test->changeMe = 'changed';
        $test->created = null;

        $this->dm->flush();

        $this->assertEquals('changed', $this->node->getNode('protected')->getProperty('change_me')->getString());
    }
}

/**
 * A class that contains mapped children via properties
 *
 * @PHPCRODM\Document(mixins={"mix:created"})
 */
class TestObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Date(property="jcr:created") */
    public $created;

    /** @PHPCRODM\String(property="change_me") */
    public $changeMe;

}
