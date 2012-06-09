<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that uses the repository strategy to generate IDs
 * 
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository")
 */
class RepositoryMappingObject
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
}
