<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Object which extends a referenceable object and so should also
 * be referenceable.
 *
 * @PHPCRODM\Document()
 */
class ReferenceableChildMappingObject extends ReferenceableMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
}

