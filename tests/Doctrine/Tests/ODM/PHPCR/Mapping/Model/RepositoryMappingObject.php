<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses the repository strategy to generate IDs.
 *
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository")
 */
#[PHPCR\Document(repositoryClass: DocumentRepository::class)]
class RepositoryMappingObject
{
    /** @PHPCRODM\Id(strategy="repository") */
    #[PHPCR\Id(strategy: 'repository')]
    public $id;
}
