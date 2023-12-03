<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses Lifecycle Callbacks.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class LifecycleCallbackMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\PreRemove */
    #[PHPCR\PreRemove]
    public function preRemoveFunc()
    {
    }

    /** @PHPCRODM\PostRemove */
    #[PHPCR\PostRemove]
    public function postRemoveFunc()
    {
    }

    /** @PHPCRODM\PrePersist */
    #[PHPCR\PrePersist]
    public function prePersistFunc()
    {
    }

    /** @PHPCRODM\PostPersist */
    #[PHPCR\PostPersist]
    public function postPersistFunc()
    {
    }

    /** @PHPCRODM\PreUpdate */
    #[PHPCR\PreUpdate]
    public function preUpdateFunc()
    {
    }

    /** @PHPCRODM\PostUpdate */
    #[PHPCR\PostUpdate]
    public function postUpdateFunc()
    {
    }

    /** @PHPCRODM\PostLoad */
    #[PHPCR\PostLoad]
    public function postLoadFunc()
    {
    }
}
