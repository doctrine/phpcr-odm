<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that represents a parent class for the purposes
 * of testing class property inheritance
 * 
 * @PHPCRODM\Document(
 *   referenceable=false, 
 *   nodeType="nt:test-override", 
 *   translator="bar",
 *   repositoryClass="Barfoo",
 *   versionable="full"
 * )
 */
class ClassInheritanceChildOverridesMappingObject extends ClassInheritanceParentMappingObject
{
}

