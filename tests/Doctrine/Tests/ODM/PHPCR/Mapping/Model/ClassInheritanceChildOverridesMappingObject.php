<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that represents a parent class for the purposes
 * of testing class property inheritance
 *
 * @PHPCRODM\Document(
 *   nodeType="nt:test-override",
 *   mixins="mix:baz",
 *   translator="bar",
 *   repositoryClass="BarfooRepository",
 *   versionable="full"
 * )
 */
class ClassInheritanceChildOverridesMappingObject extends ClassInheritanceParentMappingObject
{
}

