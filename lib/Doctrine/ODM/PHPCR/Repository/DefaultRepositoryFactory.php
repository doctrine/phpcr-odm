<?php

namespace Doctrine\ODM\PHPCR\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;

/**
 * This factory is used to create default repository objects for entities at runtime.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 *
 * @since 1.1
 */
class DefaultRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of DocumentRepository instances.
     *
     * @var array<\Doctrine\Common\Persistence\ObjectRepository>
     */
    private $repositoryList = [];

    /**
     * {@inheritdoc}
     */
    public function getRepository(DocumentManagerInterface $dm, $documentName)
    {
        $documentName = ltrim($documentName, '\\');

        if (isset($this->repositoryList[$documentName])) {
            return $this->repositoryList[$documentName];
        }

        $repository = $this->createRepository($dm, $documentName);

        $this->repositoryList[$documentName] = $repository;

        return $repository;
    }

    /**
     * Create a new repository instance for a document class.
     *
     * @param DocumentManagerInterface $dm           The DocumentManager instance.
     * @param string                   $documentName The name of the document.
     *
     * @return ObjectRepository
     */
    protected function createRepository(DocumentManagerInterface $dm, $documentName)
    {
        $metadata = $dm->getClassMetadata($documentName);
        $repositoryClassName = $metadata->customRepositoryClassName;

        if (null === $repositoryClassName) {
            $configuration = $dm->getConfiguration();
            $repositoryClassName = $configuration->getDefaultRepositoryClassName();
        }

        return new $repositoryClassName($dm, $metadata);
    }
}
