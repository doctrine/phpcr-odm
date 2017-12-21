<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\NodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * Operations that a translation strategy must support.
 *
 * A translation strategy is responsible for storing translations to PHPCR and
 * retrieving them again.
 */
interface TranslationStrategyInterface
{
    /**
     * Save the translatable fields of a node.
     *
     * @param array         $data     Data to save (field name => value to persist)
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     * @param string        $locale   The language to persist the translations to
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Load the translatable fields of a node.
     *
     * Either loads all translatable fields into the document and returns true or
     * returns false if this is not possible.
     *
     * @param object        $document The document in which to load the data
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     * @param string        $locale   The language to load the translations from
     *
     * @return bool true if the translation was completely loaded, false otherwise
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Removes all the translated fields for all translations of this node.
     * This will only be called just before the node itself is removed.
     *
     * @param object        $document The document from which the translations must be removed
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata);

    /**
     * Remove the translated fields of a node in a given language.
     *
     * The document object is not altered by this operation.
     *
     * @param object        $document The document from which the translations must be removed
     * @param NodeInterface $node     The physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     * @param string        $locale   The language to remove
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale);

    /**
     * Get the list of locales persisted for this node.
     *
     * @param object        $document The document that must be checked
     * @param NodeInterface $node     The Physical node in the content repository
     * @param ClassMetadata $metadata The Doctrine metadata of the document
     *
     * @return array with the locales strings
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata);

    /**
     * Get the location of the property for the base property name in a given
     * language.
     *
     * @param string $alias        The selector alias of the main node.
     * @param string $propertyName The base name of the translated property.
     * @param string $locale       The requested locale.
     *
     * @return array with first alias, then the real property name.
     *
     * @since 1.1
     */
    public function getTranslatedPropertyPath($alias, $propertyName, $locale);

    /**
     * This method allows a translation strategy to alter the query to
     * integrate translations that are on other nodes.
     *
     * Only called once per alias value. The selector and constraint are passed
     * by reference, the strategy can alter them to let the ConverterInterface instance
     * generate a different query.
     *
     * @param QueryObjectModelFactoryInterface $qomf       The PHPCR query factory.
     * @param SourceInterface                  $selector   The current selector.
     * @param ConstraintInterface|null         $constraint The current constraint, may be empty.
     * @param string                           $alias      The selector alias of the main node.
     * @param string                           $locale     The language to use.
     *
     * @since 1.1
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SourceInterface &$selector,
        ConstraintInterface &$constraint = null,
        $alias,
        $locale
    );
}
