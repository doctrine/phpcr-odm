<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class HardRefTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceOne(targetDocument: RefRefTestObj::class, strategy: 'hard', cascade: 'persist')]
    public $reference;
}
