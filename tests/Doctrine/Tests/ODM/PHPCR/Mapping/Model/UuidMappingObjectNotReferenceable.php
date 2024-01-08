<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * An invalid document that has the uuid mapped but is not referenceable.
 */
#[PHPCR\Document(referenceable: false)]
class UuidMappingObjectNotReferenceable
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Uuid]
    public $uuid;
}
