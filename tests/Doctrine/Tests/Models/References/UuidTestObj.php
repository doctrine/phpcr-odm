<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class UuidTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Uuid]
    public $uuid1;
}
