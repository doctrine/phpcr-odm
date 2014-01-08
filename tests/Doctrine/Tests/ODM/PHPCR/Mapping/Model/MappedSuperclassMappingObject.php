<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that uses the repository strategy to generate IDs
 *
 * @PHPCRODM\MappedSuperclass(nodeType="phpcr:test", repositoryClass="Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository", translator="children", mixins={"mix:one", "mix:two"}, versionable="simple", referenceable=true)
 */
class MappedSuperclassMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
}
