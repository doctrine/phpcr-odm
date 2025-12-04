<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * Generate the id from the nodename and the parent mapping fields. Simply uses
 * the parent id and appends the nodename field.
 */
class ParentIdGenerator extends IdGenerator
{
    /**
     * Use the name and parent fields to generate the id.
     *
     * {@inheritdoc}
     */
    public function generate(object $document, ClassMetadata $metadata, DocumentManagerInterface $dm, ?object $parent = null): string
    {
        if (null === $parent) {
            $parent = $metadata->parentMapping ? $metadata->getFieldValue($document, $metadata->parentMapping) : null;
        }

        $name = $metadata->nodename ? $metadata->getFieldValue($document, $metadata->nodename) : null;
        $id = $metadata->identifier ? $metadata->getFieldValue($document, $metadata->identifier) : null;

        if (empty($id)) {
            if (empty($name) && empty($parent)) {
                throw IdException::noIdentificationParameters($document, $metadata->parentMapping, $metadata->nodename);
            }

            if (empty($parent)) {
                throw IdException::noIdNoParent($document, $metadata->parentMapping);
            }

            if (empty($name)) {
                throw IdException::noIdNoName($document, $metadata->nodename);
            }
        }

        // use assigned ID by default
        if (empty($parent) || empty($name)) {
            return $id;
        }

        if ($metadata->isValidNodename($name)) {
            throw IdException::illegalName($document, $metadata->nodename, $name);
        }

        // determine ID based on the path and the node name
        return $this->buildName($document, $metadata, $dm, $parent, $name);
    }

    protected function buildName(object $document, ClassMetadata $metadata, DocumentManagerInterface $dm, object $parent, string $name): string
    {
        // get the id of the parent document
        $id = $dm->getUnitOfWork()->getDocumentId($parent);
        if (!$id) {
            throw IdException::parentIdCouldNotBeDetermined($document, $metadata->parentMapping, $parent);
        }

        // edge case parent is root
        if ('/' === $id) {
            $id = '';
        }

        return $id.'/'.$name;
    }
}
