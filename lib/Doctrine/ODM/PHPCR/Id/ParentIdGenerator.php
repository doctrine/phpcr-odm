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
    public function generate(object $document, ClassMetadata $class, DocumentManagerInterface $dm, object $parent = null): string
    {
        if (null === $parent) {
            $parent = $class->parentMapping ? $class->getFieldValue($document, $class->parentMapping) : null;
        }

        $name = $class->nodename ? $class->getFieldValue($document, $class->nodename) : null;
        $id = $class->identifier ? $class->getFieldValue($document, $class->identifier) : null;

        if (empty($id)) {
            if (empty($name) && empty($parent)) {
                throw IdException::noIdentificationParameters($document, $class->parentMapping, $class->nodename);
            }

            if (empty($parent)) {
                throw IdException::noIdNoParent($document, $class->parentMapping);
            }

            if (empty($name)) {
                throw IdException::noIdNoName($document, $class->nodename);
            }
        }

        // use assigned ID by default
        if (empty($parent) || empty($name)) {
            return $id;
        }

        if ($class->isValidNodename($name)) {
            throw IdException::illegalName($document, $class->nodename, $name);
        }

        // determine ID based on the path and the node name
        return $this->buildName($document, $class, $dm, $parent, $name);
    }

    protected function buildName(object $document, ClassMetadata $class, DocumentManagerInterface $dm, object $parent, string $name): string
    {
        // get the id of the parent document
        $id = $dm->getUnitOfWork()->getDocumentId($parent);
        if (!$id) {
            throw IdException::parentIdCouldNotBeDetermined($document, $class->parentMapping, $parent);
        }

        // edge case parent is root
        if ('/' === $id) {
            $id = '';
        }

        return $id.'/'.$name;
    }
}
