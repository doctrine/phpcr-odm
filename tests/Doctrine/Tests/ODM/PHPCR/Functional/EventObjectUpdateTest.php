<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

class EventObjectUpdateTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var TestEventDocumentChanger
     */
    private $listener;

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->listener = new TestEventDocumentChanger2();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }


    public function testComputingBetweenEvents()
    {
        $this->dm
            ->getEventManager()
            ->addEventListener(
                array(
                    Event::postLoad,
                    Event::prePersist,
                    Event::preUpdate,
                    Event::postPersist,
                    Event::postUpdate,
                ),
                $this->listener
            );

        $entity = new SomeEntity;
        $entity->id = '/functional/test';
        $entity->status = new \stdClass();
        $entity->status->value = 'active';
        $entity->status->foo = 'bar';
        $entity->text = 'test1';

        $this->dm->persist($entity);

        $this->dm->flush();

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertAttributeNotEmpty('value', $entity->status);
        $this->assertEquals($entity->status->value, 'active');
        $this->assertObjectNotHasAttribute('foo', $entity->status);

        $entity->status->value = 'inactive';
        $entity->status->foo = 'bar2';
        $entity->text = 'test2';

        $this->dm->flush();

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertAttributeNotEmpty('value', $entity->status);
        $this->assertEquals($entity->status->value, 'inactive');
        $this->assertObjectNotHasAttribute('foo', $entity->status);
        $this->assertEquals($entity->text, 'test2');

        $this->dm->clear();

        $entity = $this->dm->find(null, $entity->id);

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertAttributeNotEmpty('value', $entity->status);
        $this->assertEquals($entity->status->value, 'inactive');
        $this->assertObjectNotHasAttribute('foo', $entity->status);
        $this->assertEquals($entity->text, 'test2');

        $entity->status->value = 'active';

        $this->dm->flush();

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertAttributeNotEmpty('value', $entity->status);
        $this->assertEquals($entity->status->value, 'active');
        $this->assertEquals($entity->text, 'test2');
    }
}

/**
 * @PHPCRODM\Document()
 */
class SomeEntity
{
    /** @PHPCRODM\Id() */
    public $id;

    /** @PHPCRODM\String(nullable=true) */
    public $status;

    /** @PHPCRODM\String() */
    public $text;
}

class TestEventDocumentChanger2
{
    public function getSubscribedEvents()
    {
        return array(
            'postLoad',
            'prePersist',
            'preUpdate',
            'postPersist',
            'postUpdate',
        );
    }

    protected function switchToObject(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $status = new \stdClass();
        $status->value = $entity->status;
        $entity->status = $status;
    }

    protected function switchToId(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $entity->status = $entity->status->value;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $this->switchToObject($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->switchToObject($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->switchToObject($args);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->switchToId($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->switchToId($args);
    }
}
