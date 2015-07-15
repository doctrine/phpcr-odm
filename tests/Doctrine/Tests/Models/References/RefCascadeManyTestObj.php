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
    /** @PHPCRODM\Property(type="string") */
    public $name;

    public function __construct()
    {
       $this->references = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
