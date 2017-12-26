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
     * @param DocumentManagerInterface $dm           The DocumentManager instance.
     * @param string                   $documentName The name of the document.
     *
     * @return ObjectRepository
     */
    public function getRepository(DocumentManagerInterface $dm, $documentName);
}
