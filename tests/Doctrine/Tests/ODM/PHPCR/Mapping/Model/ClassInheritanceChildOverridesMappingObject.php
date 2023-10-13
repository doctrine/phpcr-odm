<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that represents a parent class for the purposes
 * of testing class property inheritance
 *
 * @PHPCRODM\Document(
 *   nodeType="nt:test-override",
 *   mixins="mix:baz",
 *   inheritMixins=false,
 *   translator="bar",
 *   repositoryClass="BarfooRepository",
 *   versionable="full"
 * )
 */
#[PHPCR\Document(
    nodeType: 'nt:test-override',
    mixins: ['mix:baz'],
    inheritMixins: false,
    translator: 'bar',
    repositoryClass: BarfooRepository::class,
    versionable: 'full',
)]
class ClassInheritanceChildOverridesMappingObject extends ClassInheritanceParentMappingObject
{
}
