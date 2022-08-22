<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\MixinMappingObject;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\NodeType\ConstraintViolationException;

/**
 * @group functional
 */
class MixinTest extends PHPCRFunctionalTestCase
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

    public function testMixin(): void
    {
        $mixin = new MixinMappingObject();
        $mixin->id = '/functional/mixin';

        $this->dm->persist($mixin);
        $this->dm->flush();

        $this->assertTrue($mixin->node->hasProperty('jcr:lastModified'));
        $this->assertTrue($mixin->node->hasProperty('jcr:lastModifiedBy'));
        $lastModified = $mixin->node->getPropertyValue('jcr:lastModified');
        $this->assertNotNull($lastModified);
        $this->assertNull($mixin->lastModified);
        $this->assertNotNull($mixin->node->getPropertyValue('jcr:lastModifiedBy'));

        $mixin->lastModified = new \DateTime();
        $mixin->lastModified->add(new \DateInterval('P0Y0M0DT0H0M1S'));
        $this->dm->flush();
        $this->dm->clear();

        $mixin = $this->dm->find(null, '/functional/mixin');
        $this->assertNotEquals($lastModified, $mixin->lastModified);
        $lastModified = $mixin->node->getPropertyValue('jcr:lastModified');
        $this->assertNotNull($lastModified);
        $this->assertNotNull($mixin->lastModified);
        $this->assertEquals($lastModified, $mixin->lastModified);
    }

    public function testProtectedPropertyIsCreatedAndNotChanged(): void
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

        $this->assertEquals($created->getTimestamp(), $this->node->getNode('protected')->getProperty('jcr:created')->getDate()->getTimestamp());
        $this->assertEquals('changed', $this->node->getNode('protected')->getProperty('change_me')->getString());
    }

    public function testChangingProtectedPropertyThrowsException(): void
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

        $this->expectException(ConstraintViolationException::class);
        $this->dm->flush();
    }

    public function testChangingProtectedPropertyToNullThrowsException(): void
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

        $this->expectException(ConstraintViolationException::class);
        $this->dm->flush();
    }
}

/**
 * A class that contains mapped children via properties.
 *
 * @PHPCRODM\Document(mixins={"mix:created"})
 */
class TestObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="date", property="jcr:created") */
    public $created;

    /** @PHPCRODM\Field(type="string", property="change_me") */
    public $changeMe;
}
