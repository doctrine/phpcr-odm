<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

interface TranslationStrategyInterface
{
    /**
     * Save the translatable fields of a node
     *
     * @param array $data Data to save (field name => value to persist)
     * @param \PHPCR\NodeInterface $node The physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     * @param $locale The language to persist the translations to
     */
    function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Load the translatable fields of a node.
     *
     * Either loads all translatable fields into the document and returns true or
     * returns false if this is not possible.
     *
     * @param $document The document in which to load the data
     * @param \PHPCR\NodeInterface $node The physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     * @param $locale The language to load the translations from
     *
     * @return true if the translation was completely loaded, false otherwise
     */
    function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Removes all the translated fields for all translations of this node.
     * This will only be called just before the node itself is removed.
     *
     * @param $document The document from which the translations must be removed
     * @param \PHPCR\NodeInterface $node The physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     */
    function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata);

    /**
     * Remove the translated fields of a node in a given language
     *
     * The document object is not altered by this operation.
     *
     * @param $document The document from which the translations must be removed
     * @param NodeInterface $node The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     * @param $locale The language to remove
     */
    function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Get the list of locales persisted for this node
     *
     * @param $document The document that must be checked
     * @param NodeInterface $node The Physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     *
     * @return array with the locales strings
     */
    function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata);
}
