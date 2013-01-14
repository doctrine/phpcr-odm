<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that represents a child class for the purposes
 * of testing class property inheritance
 * 
 * @PHPCRODM\Document()
 */
class ClassInheritanceChildMappingObject extends ClassInheritanceParentMappingObject
{
}

