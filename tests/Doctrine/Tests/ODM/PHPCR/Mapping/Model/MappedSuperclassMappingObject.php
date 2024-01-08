<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses the repository strategy to generate IDs.
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
    #[PHPCR\Id]
    public $id;
}
