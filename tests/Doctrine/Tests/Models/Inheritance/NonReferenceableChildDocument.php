<?php

namespace Doctrine\Tests\Models\Inheritance;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=false)
 */
class NonReferenceableChildDocument extends ReferenceableParentDocument
{
}


