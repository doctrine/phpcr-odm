<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * An object that extends a referenceable object but sets
 * referenceable to FALSE, which is not permitted.
 *
 * @PHPCRODM\Document(referenceable=false)
 */
class ReferenceableChildReferenceableFalseMappingObject extends ReferenceableMappingObject
{
}

