<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

class EventObjectUpdateTest extends PHPCRFunctionalTestCase
{
    /**
     * @var TestEventDocumentChanger
     */
    private $listener;

    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->listener = new TestEventDocumentChanger2();
        $this->dm = $this->createDocumentManager();
    }

    public function testComputingBetweenEvents(): void
    {
        $this->dm
            ->getEventManager()
            ->addEventListener(
                [
                    Event::postLoad,
                    Event::prePersist,
                    Event::preUpdate,
                    Event::postPersist,
                    Event::postUpdate,
                ],
                $this->listener
            );

        $entity = new SomeEntity();
        $entity->id = '/functional/test';
        $entity->status = new \stdClass();
        $entity->status->value = 'active';
        $entity->status->foo = 'bar';
        $entity->text = 'test1';

        $this->dm->persist($entity);

        $this->dm->flush();

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertObjectHasProperty('value', $entity->status);
        $this->assertSame('active', $entity->status->value);
        $this->assertObjectNotHasProperty('foo', $entity->status);

        $entity->status->value = 'inactive';
        $entity->status->foo = 'bar2';
        $entity->text = 'test2';

        $this->dm->flush();

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertObjectHasProperty('value', $entity->status);
        $this->assertSame('inactive', $entity->status->value);
        $this->assertObjectNotHasProperty('foo', $entity->status);
        $this->assertSame('test2', $entity->text);

        $this->dm->clear();

        $entity = $this->dm->findDocument($entity->id);

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertObjectHasProperty('value', $entity->status);
        $this->assertSame('inactive', $entity->status->value);
        $this->assertObjectNotHasProperty('foo', $entity->status);
        $this->assertSame('test2', $entity->text);

        $entity->status->value = 'active';

        $this->dm->flush();

        $this->assertInstanceOf('stdClass', $entity->status);
        $this->assertObjectHasProperty('value', $entity->status);
        $this->assertSame('active', $entity->status->value);
        $this->assertSame('test2', $entity->text);
    }
}

#[PHPCR\Document]
class SomeEntity
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $status;

    #[PHPCR\Field(type: 'string')]
    public $text;
}

class TestEventDocumentChanger2
{
    public function getSubscribedEvents(): array
    {
        return [
            'postLoad',
            'prePersist',
            'preUpdate',
            'postPersist',
            'postUpdate',
        ];
    }

    protected function switchToObject(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();
        $status = new \stdClass();
        $status->value = $object->status;
        $object->status = $status;
    }

    protected function switchToId(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        $object->status = $object->status->value;
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $this->switchToObject($args);
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->switchToObject($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->switchToObject($args);
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->switchToId($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->switchToId($args);
    }
}
