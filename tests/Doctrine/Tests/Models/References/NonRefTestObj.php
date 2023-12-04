<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: false)]
class NonRefTestObj
{
    #[PHPCR\Id]
    public $id;
}
