<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * Object which extends a referenceable object and so should also
 * be referenceable.
 */
#[PHPCR\Document]
class ReferenceableChildMappingObject extends ReferenceableMappingObject
{
    #[PHPCR\Id]
    public $id;
}
