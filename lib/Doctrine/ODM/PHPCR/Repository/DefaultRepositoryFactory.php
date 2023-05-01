<?php

namespace Doctrine\ODM\PHPCR\Repository;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\Persistence\ObjectRepository;

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
     * @var array<ObjectRepository>
     */
    private array $repositoryList = [];

    public function getRepository(DocumentManagerInterface $dm, string $documentClass): ObjectRepository
    {
        $documentClass = ltrim($documentClass, '\\');

        if (array_key_exists($documentClass, $this->repositoryList)) {
            return $this->repositoryList[$documentClass];
        }

        $repository = $this->createRepository($dm, $documentClass);

        $this->repositoryList[$documentClass] = $repository;

        return $repository;
    }

    protected function createRepository(DocumentManagerInterface $dm, string $documentClass): ObjectRepository
    {
        $metadata = $dm->getClassMetadata($documentClass);
        $repositoryClassName = $metadata->customRepositoryClassName;

        if (null === $repositoryClassName) {
            $configuration = $dm->getConfiguration();
            $repositoryClassName = $configuration->getDefaultRepositoryClassName();
        }

        return new $repositoryClassName($dm, $metadata);
    }
}
