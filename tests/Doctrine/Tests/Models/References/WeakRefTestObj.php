<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class WeakRefTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceOne(targetDocument: RefRefTestObj::class, strategy: 'weak', cascade: 'persist')]
    public $reference;
}
