<?php

namespace Doctrine\ODM\PHPCR\Repository;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface for document repository factory.
 *
 * @since 1.1
 */
interface RepositoryFactory
{
    public function getRepository(DocumentManagerInterface $dm, string $documentClass): ObjectRepository;
}
