<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class RefTestObjByPath
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceOne(targetDocument: RefRefTestObj::class, strategy: 'path', cascade: 'persist')]
    public $reference;
}
