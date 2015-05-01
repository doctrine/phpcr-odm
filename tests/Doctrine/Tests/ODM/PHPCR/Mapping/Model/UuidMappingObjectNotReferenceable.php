<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * An invalid document that has the uuid mapped but is not referenceable.
 *
 * @PHPCRODM\Document(referenceable=false)
 */
class UuidMappingObjectNotReferenceable
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Uuid() */
    public $uuid;
}
