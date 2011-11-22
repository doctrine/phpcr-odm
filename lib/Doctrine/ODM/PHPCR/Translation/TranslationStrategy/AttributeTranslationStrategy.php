<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

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
    protected $prefix = 'lang';

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
    public function saveTranslation($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        // TODO: lang could be null... --> use LanguageChooserStrategy
        foreach ($metadata->translatableFields as $field) {

            $propName = $this->getTranslatedPropertyName($lang, $field);
            $node->setProperty($propName, $document->$field);
        }

        // Update the document locale if the field exists and it is null
        if ($localeField = $metadata->localeMapping['fieldName']) {
            if (is_null($document->$localeField)) {
                $document->$localeField = $lang;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        // TODO: lang could be null... --> use LanguageChooserStrategy
        foreach ($metadata->translatableFields as $field) {
            $propName = $this->getTranslatedPropertyName($lang, $field);
            $document->$field = $node->getPropertyValue($propName);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        foreach ($metadata->translatableFields as $field)
        {
            $propName = $this->getTranslatedPropertyName($lang, $field);
            $prop = $node->getProperty($propName);
            $prop->remove();

            // TODO: what values should the document take for those removed translated properties?
            $document->$field = null;
        }

        // Update the locale if we removed the current locale
        if ($localField = $metadata->localeMapping['fieldName']) {
            if ($document->$localField === $lang) {
                $document->$localField = null;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $languages = array(); // TODO: get the list of availlable languages
        foreach ($languages as $lang) {
            $this->removeTranslation($document, $node, $metadata, $lang);
        }
    }

    /**
     * Get the name of the property where to store the translations of a given property in a given language
     * @param $lang The language to store
     * @param $fieldName The name of the field to translate
     * @return string The name of the property where to store the translation
     */
    protected function getTranslatedPropertyName($lang, $fieldName)
    {
        return sprintf('%s-%s-%s', $this->prefix, $lang, $fieldName);
    }
}
