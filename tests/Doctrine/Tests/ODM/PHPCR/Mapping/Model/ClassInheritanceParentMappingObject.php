<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that represents a parent class for the purposes
 * of testing class property inheritance.
 */
#[PHPCR\Document(
    nodeType: 'nt:test',
    repositoryClass: DocumentRepository::class,
    translator: 'foo',
    mixins: ['mix:foo', 'mix:bar'],
    versionable: 'simple',
    referenceable: true,
)]
class ClassInheritanceParentMappingObject
{
    #[PHPCR\Id]
    public $id;
}
