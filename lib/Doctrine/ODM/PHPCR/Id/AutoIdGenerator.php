<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\RepositoryException;
use PHPCR\Util\NodeHelper;

/**
 * Generate the id using the auto naming strategy.
 */
class AutoIdGenerator extends ParentIdGenerator
{
    /**
     * Use the parent field together with an auto generated name to generate the id.
     *
     * {@inheritdoc}
     */
    public function generate(object $document, ClassMetadata $metadata, DocumentManagerInterface $dm, ?object $parent = null): string
    {
        if (null === $parent) {
            $parent = $metadata->parentMapping ? $metadata->getFieldValue($document, $metadata->parentMapping) : null;
        }

        $id = $metadata->identifier ? $metadata->getFieldValue($document, $metadata->identifier) : null;
        if (empty($id) && null === $parent) {
            throw IdException::noIdNoParent($document, $metadata->parentMapping);
        }

        if (empty($parent)) {
            return $id;
        }

        try {
            $parentNode = $dm->getNodeForDocument($parent);
            $existingNames = (array) $parentNode->getNodeNames();
        } catch (RepositoryException $e) {
            // this typically happens while cascading persisting documents
            $existingNames = [];
        }
        $name = NodeHelper::generateAutoNodeName(
            $existingNames,
            $dm->getPhpcrSession()->getWorkspace()->getNamespaceRegistry()->getNamespaces(),
            '',
            ''
        );

        return $this->buildName($document, $metadata, $dm, $parent, $name);
    }
}
