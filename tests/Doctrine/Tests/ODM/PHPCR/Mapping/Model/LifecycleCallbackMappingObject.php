<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that uses Lifecycle Callbacks
 * 
 * @PHPCRODM\Document
 */
class LifecycleCallbackMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\PreRemove */
    public function preRemoveFunc()
    {
        
    }

    /** @PHPCRODM\PostRemove */
    public function postRemoveFunc()
    {
        
    }

    /** @PHPCRODM\PrePersist */
    public function prePersistFunc()
    {
        
    }

    /** @PHPCRODM\PostPersist */
    public function postPersistFunc()
    {
        
    }

    /** @PHPCRODM\PreUpdate */
    public function preUpdateFunc()
    {
        
    }

    /** @PHPCRODM\PostUpdate */
    public function postUpdateFunc()
    {
        
    }

    /** @PHPCRODM\PostLoad */
    public function postLoadFunc()
    {
        
    }
}
