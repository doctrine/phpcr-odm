<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class RefManyTestObjForCascade
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceMany(targetDocument: RefCascadeManyTestObj::class, cascade: 'persist')]
    public $references;
}
