<?php

namespace Doctrine\ODM\PHPCR\Tools\Helper;

use PHPCR\NodeInterface;
use PHPCR\Util\PathHelper;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * This helper collects information about what nodes will be loaded when
 * creating a document proxy and allows to load them in one go, even for a
 * collection.
 *
 * The trick is to gather as many paths and UUID as possible to fetch them in a
 * single call. Once the transport cached them, we can use normal PHPCR calls
 * to access them, keeping the code readable.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class PrefetchHelper
{
    /**
     * @param NodeInterface[] $nodes
     */
    public function prefetch(DocumentManager $dm, $nodes, $locale = null)
    {
        if (!count($nodes)) {
            return;
        }
        $uuids = array();
        $paths = array();
        $documentClassMapper = $dm->getConfiguration()->getDocumentClassMapper();

        foreach ($nodes as $node) {
            $className = $documentClassMapper->getClassName($dm, $node);
            $class = $dm->getClassMetadata($className);
            if (!$locale && $class->translator) {
                $locale = $dm->getLocaleChooserStrategy()->getLocale();
            }
            $uuids = array_merge($uuids, $this->collectPrefetchReferences($class, $node));
            $paths = array_merge($paths, $this->collectPrefetchHierarchy($class, $node, $locale));
        }

        if (count($uuids)) {
            $node->getSession()->getNodesByIdentifier($uuids);
        }
        if (count($paths)) {
            $node->getSession()->getNodes($paths);
        }
    }

    /**
     * Prefetch all mapped ReferenceOne annotations
     *
     * @param ClassMetadata $class  The metadata about the document to know what to do.
     * @param NodeInterface $node   The node to prefetch parent and childs for.
     */
    public function prefetchReferences(ClassMetadata $class, NodeInterface $node)
    {
        $prefetch = $this->collectPrefetchReferences($class, $node);
        if (count($prefetch)) {
            $node->getSession()->getNodesByIdentifier($prefetch);
        }
    }

    /**
     * Prefetch all Child mappings and the ParentDocument if annotations exist.
     *
     * @param ClassMetadata $class  The metadata about the document to know what to do.
     * @param NodeInterface $node   The node to prefetch parent and childs for.
     * @param string|null   $locale The locale to also prefetch the translation
     *      child if applicable.
     */
    public function prefetchHierarchy(ClassMetadata $class, NodeInterface $node, $locale = null)
    {
        $prefetch = $this->collectPrefetchHierarchy($class, $node, $locale);
        if (count($prefetch)) {
            $node->getSession()->getNodes($prefetch);
        }
    }

    /**
     * Gather all UUIDs to pre-fetch nodes in MANY_TO_ONE mappings.
     *
     * @param ClassMetadata $class The metadata about the document to know what to do.
     * @param NodeInterface $node  The node to prefetch parent and childs for.
     *
     * @return array List of UUID to fetch in one go.
     */
    public function collectPrefetchReferences(ClassMetadata $class, NodeInterface $node)
    {
        $refNodeUUIDs = array();
        foreach ($class->referenceMappings as $fieldName) {
            $mapping = $class->mappings[$fieldName];
            if (!$node->hasProperty($mapping['property'])) {
                continue;
            }

            if ($mapping['type'] & ClassMetadata::MANY_TO_ONE
                && $mapping['strategy'] !== 'path'
            ) {
                $refNodeUUIDs[] = $node->getProperty($mapping['property'])->getString();
            }
        }

        return $refNodeUUIDs;
    }

    /**
     * Gather the parent and all child mappings so they can be fetched in one
     * go.
     *
     * @param ClassMetadata $class  The metadata about the document to know what to do.
     * @param NodeInterface $node   The node to prefetch parent and childs for.
     * @param string|null   $locale The locale to also prefetch the translation
     *      child if applicable.
     *
     * @return array List of absolute paths to nodes that should be prefetched.
     */
    public function collectPrefetchHierarchy(ClassMetadata $class, NodeInterface $node, $locale = null)
    {
        $prefetch = array();
        if ($class->parentMapping && $node->getDepth() > 0) {
            $prefetch[] = PathHelper::getParentPath($node->getPath());
        }
        foreach ($class->childMappings as $fieldName) {
            $childName = $class->mappings[$fieldName]['nodeName'];
            $prefetch[] = PathHelper::absolutizePath($childName, $node->getPath());
        }
        if ($locale && count($prefetch) && 'child' === $class->translator) {
            $prefetch[] = $node->getPath() . '/phpcr_locale:'.$locale;
        }

        return $prefetch;
    }

}
