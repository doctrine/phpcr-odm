<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

interface TranslationStrategyInterface
{
    /**
     * Save the translatable fields of a node
     * @abstract
     * @param $document The document containing the data
     * @param \PHPCR\NodeInterface $node The physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     * @param $locale The language to persist the translations to
     */
    public function saveTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Load the translatable fields of a node
     * @abstract
     * @param $document The document in which to load the data
     * @param \PHPCR\NodeInterface $node The physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     * @param $locale The language to load the translations from
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Removes all the translated fields of a node
     * @abstract
     * @param $document The document from which the translations must be removed
     * @param \PHPCR\NodeInterface $node The physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata);

    /**
     * Remove the translated fields of a node in a given language
     * @abstract
     * @param $document The document from which the translations must be removed
     * @param \PHPCR\NodeInterface $node The physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     * @param $locale The language to remove
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Get the list of locales persisted for a node
     * @abstract
     * @param $document The document that must be checked
     * @param \PHPCR\NodeInterface $node The Physical node in the content repository
     * @param \Doctrine\ODM\PHPCR\Mapping\ClassMetadata $metadata The Doctrine metadata of the document
     * @return array
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata);
}
