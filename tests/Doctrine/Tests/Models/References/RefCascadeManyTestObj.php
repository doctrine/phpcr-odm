<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class RefCascadeManyTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceMany(targetDocument="RefRefTestObj", cascade="persist") */
    public $references;
    /** @PHPCRODM\String */
    public $name;

    public function __construct()
    {
       $this->references = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
