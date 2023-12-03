<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * An invalid document that has the uuid mapped but is not referenceable.
 *
 * @PHPCRODM\Document(referenceable=false)
 */
#[PHPCR\Document(referenceable: false)]
class UuidMappingObjectNotReferenceable
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\Uuid */
    #[PHPCR\Uuid]
    public $uuid;
}
