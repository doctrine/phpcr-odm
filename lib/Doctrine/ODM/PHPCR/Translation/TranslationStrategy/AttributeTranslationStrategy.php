<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

use Doctrine\ODM\PHPCR\Translation\Translation;

/**
 * Translation strategy that stores the translations in attributes of the same node.
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class AttributeTranslationStrategy implements TranslationStrategyInterface
{
    /*** @var string */
    protected $prefix = Translation::LOCALE_NAMESPACE;

    /**
     * Set the prefix to use to determine the name of the property where translations are stored
     * @param $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function saveTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($metadata->translatableFields as $field) {

            $propName = $this->getTranslatedPropertyName($locale, $field);
            $node->setProperty($propName, $document->$field);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($metadata->translatableFields as $field) {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            if (!$node->hasProperty($propName)) {
                // Could not find the translation in the given language
                return false;
            }
            $document->$field = $node->getPropertyValue($propName);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($metadata->translatableFields as $field)
        {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            $prop = $node->getProperty($propName);
            $prop->remove();

            // TODO: what values should the document take for those removed translated properties?
            $document->$field = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        // Do nothing: if the node is removed then all it's translated properties will be removed
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $locales = array();
        foreach ($node->getProperties("*{$this->prefix}*") as $prop) {
            $matches = null;
            if (preg_match('/' . $this->prefix . '-(..)-[^-]*/', $prop->getName(), $matches)) {
                if (is_array($matches) && count($matches) > 1 && !in_array($matches[1], $locales)) {
                    $locales[] = $matches[1];
                }
            }
        }
        return $locales;
    }

    /**
     * Get the name of the property where to store the translations of a given property in a given language
     * @param $locale The language to store
     * @param $fieldName The name of the field to translate
     * @return string The name of the property where to store the translation
     */
    protected function getTranslatedPropertyName($locale, $fieldName)
    {
        return sprintf('%s-%s-%s', $this->prefix, $locale, $fieldName);
    }
}
