<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
#[PHPCR\Document(referenceable: true)]
class UuidMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\Uuid */
    #[PHPCR\Uuid]
    public $uuid;
}
