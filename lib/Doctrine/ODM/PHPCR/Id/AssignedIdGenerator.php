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
    public function generate($document, ClassMetadata $cm, DocumentManagerInterface $dm, $parent = null)
    {
        $id = $cm->getFieldValue($document, $cm->identifier);
        if (!$id) {
            throw new IdException('ID could not be read from the document instance using the AssignedIdGenerator.');
        }

        return $id;
    }
}
