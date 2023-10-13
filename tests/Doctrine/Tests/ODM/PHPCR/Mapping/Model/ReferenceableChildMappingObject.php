<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * Object which extends a referenceable object and so should also
 * be referenceable.
 *
 * @PHPCRODM\Document()
 */
#[PHPCR\Document]
class ReferenceableChildMappingObject extends ReferenceableMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
