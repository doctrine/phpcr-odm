<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class UuidMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Uuid]
    public $uuid;
}
