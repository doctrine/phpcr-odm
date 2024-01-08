<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses Lifecycle Callbacks.
 */
#[PHPCR\Document]
class LifecycleCallbackMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\PreRemove]
    public function preRemoveFunc()
    {
    }

    #[PHPCR\PostRemove]
    public function postRemoveFunc()
    {
    }

    #[PHPCR\PrePersist]
    public function prePersistFunc()
    {
    }

    #[PHPCR\PostPersist]
    public function postPersistFunc()
    {
    }

    #[PHPCR\PreUpdate]
    public function preUpdateFunc()
    {
    }

    #[PHPCR\PostUpdate]
    public function postUpdateFunc()
    {
    }

    #[PHPCR\PostLoad]
    public function postLoadFunc()
    {
    }
}
