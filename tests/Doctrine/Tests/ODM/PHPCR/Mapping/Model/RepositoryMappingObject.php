<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses the repository strategy to generate IDs.
 */
#[PHPCR\Document(repositoryClass: DocumentRepository::class)]
class RepositoryMappingObject
{
    #[PHPCR\Id(strategy: 'repository')]
    public $id;
}
