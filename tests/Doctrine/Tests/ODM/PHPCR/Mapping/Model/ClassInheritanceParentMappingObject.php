<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that represents a parent class for the purposes
 * of testing class property inheritance.
 *
 * @PHPCRODM\Document(
 *   referenceable=true,
 *   nodeType="nt:test",
 *   mixins={"mix:foo","mix:bar"},
 *   translator="foo",
 *   repositoryClass="DocumentRepository",
 *   versionable="simple"
 * )
 */
#[PHPCR\Document(
    nodeType: 'nt:test',
    repositoryClass: DocumentRepository::class,
    translator: 'foo',
    mixins: ['mix:foo', 'mix:bar'],
    referenceable: true,
)]
class ClassInheritanceParentMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
