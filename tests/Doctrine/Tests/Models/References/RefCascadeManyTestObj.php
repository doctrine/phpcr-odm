<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class RefCascadeManyTestObj
{
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\ReferenceMany(targetDocument="RefRefTestObj", cascade="persist") */
    #[PHPCR\ReferenceMany(targetDocument: RefRefTestObj::class, cascade: 'persist')]
    public $references;

    #[PHPCR\Field(type: 'string')]
    public $name;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }
}
