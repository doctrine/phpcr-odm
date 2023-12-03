<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that represents a parent class for the purposes
 * of testing class property inheritance.
 */
#[PHPCR\Document(
    nodeType: 'nt:test-override',
    repositoryClass: BarfooRepository::class,
    translator: 'bar',
    mixins: ['mix:baz'],
    inheritMixins: false,
    versionable: 'full',
)]
class ClassInheritanceChildOverridesMappingObject extends ClassInheritanceParentMappingObject
{
}
