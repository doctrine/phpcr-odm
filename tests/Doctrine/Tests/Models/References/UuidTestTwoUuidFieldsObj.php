<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class UuidTestTwoUuidFieldsObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Uuid]
    public $uuid1;

    #[PHPCR\Uuid]
    public $uuid2;
}
