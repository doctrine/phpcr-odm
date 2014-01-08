<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that represents a parent class for the purposes
 * of testing class property inheritance
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
class ClassInheritanceParentMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
}

