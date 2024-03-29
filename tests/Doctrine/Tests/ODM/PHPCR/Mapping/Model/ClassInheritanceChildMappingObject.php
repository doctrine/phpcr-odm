<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that represents a child class for the purposes
 * of testing class property inheritance.
 */
#[PHPCR\Document]
class ClassInheritanceChildMappingObject extends ClassInheritanceParentMappingObject
{
}
