<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses the repository strategy to generate IDs
 *
 * @PHPCRODM\MappedSuperclass(nodeType="phpcr:test", repositoryClass="Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository", translator="children", mixins={"mix:one", "mix:two"}, versionable="simple", referenceable=true)
 */
#[PHPCR\MappedSuperclass(
    nodeType: 'phpcr:test',
    repositoryClass: DocumentRepository::class,
    translator: 'children',
    mixins: ['mix:one', 'mix:two'],
    versionable: 'simple',
    referenceable: true,
)]
class MappedSuperclassMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
