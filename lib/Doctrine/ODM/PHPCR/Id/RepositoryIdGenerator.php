<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class RepositoryIdGenerator extends IdGenerator
{
    /**
     * Use a repository that implements RepositoryIdGenerator to generate the id.
     *
     * {@inheritdoc}
     */
    public function generate(object $document, ClassMetadata $metadata, DocumentManagerInterface $dm, ?object $parent = null): string
    {
        if (null === $parent) {
            $parent = $metadata->parentMapping ? $metadata->getFieldValue($document, $metadata->parentMapping) : null;
        }
        $repository = $dm->getRepository($metadata->name);
        if (!$repository instanceof RepositoryIdInterface) {
            throw new IdException("ID could not be determined. Make sure the that the Repository '".ClassUtils::getClass($repository)."' implements RepositoryIdInterface");
        }

        $id = $repository->generateId($document, $parent);
        if (!$id) {
            throw new IdException('ID could not be determined. Repository was unable to generate an ID');
        }

        return $id;
    }
}
