<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class RefTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceOne(property: 'myReference', targetDocument: RefRefTestObj::class, cascade: 'persist')]
    public $reference;
}
