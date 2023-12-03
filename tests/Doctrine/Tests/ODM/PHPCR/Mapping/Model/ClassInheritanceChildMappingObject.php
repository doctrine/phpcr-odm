<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that represents a child class for the purposes
 * of testing class property inheritance.
 *
 * @PHPCRODM\Document()
 */
#[PHPCR\Document]
class ClassInheritanceChildMappingObject extends ClassInheritanceParentMappingObject
{
}
