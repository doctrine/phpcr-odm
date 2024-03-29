<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * An object that extends a referenceable object but sets
 * referenceable to FALSE, which is not permitted.
 */
#[PHPCR\Document(referenceable: false)]
class ReferenceableChildReferenceableFalseMappingObject extends ReferenceableMappingObject
{
}
