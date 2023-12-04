<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class RefManyTestObjPathStrategy
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceMany(property: 'myReferences', targetDocument: RefRefTestObj::class, strategy: 'path', cascade: 'persist')]
    public $references;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }
}
