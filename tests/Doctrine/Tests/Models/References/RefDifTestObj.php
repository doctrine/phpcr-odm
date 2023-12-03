<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class RefDifTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceOne(targetDocument: RefType1TestObj::class, cascade: 'persist')]
    public $referenceType1;

    #[PHPCR\ReferenceOne(targetDocument: RefType2TestObj::class, cascade: 'persist')]
    public $referenceType2;
}
