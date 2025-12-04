<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * Generator to handle explicitly assigned repository paths.
 */
class AssignedIdGenerator extends IdGenerator
{
    /**
     * Use the identifier field as id and throw exception if not set.
     *
     * {@inheritdoc}
     */
    public function generate(object $document, ClassMetadata $metadata, DocumentManagerInterface $dm, ?object $parent = null): string
    {
        if (!$metadata->identifier || !$id = $metadata->getFieldValue($document, $metadata->identifier)) {
            throw new IdException('ID could not be read from the document instance using the AssignedIdGenerator.');
        }

        return $id;
    }
}
