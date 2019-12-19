<?php

namespace Doctrine\ODM\PHPCR\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;

/**
 * Interface for document repository factory.
 *
 * @since 1.1
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for a document class.
     *
     * @param DocumentManagerInterface $dm           the DocumentManager instance
     * @param string                   $documentName the name of the document
     *
     * @return ObjectRepository
     */
    public function getRepository(DocumentManagerInterface $dm, $documentName);
}
