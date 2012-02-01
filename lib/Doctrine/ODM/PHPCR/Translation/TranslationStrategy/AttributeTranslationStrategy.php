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
 * @author      David Buchmann <david@liip.ch>
 */
class AttributeTranslationStrategy extends AbstractTranslationStrategy
{
    const NULLFIELDS = 'nullfields';

    /**
     * {@inheritdoc}
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $nullFields = array();
        foreach ($data as $field => $propValue) {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            $node->setProperty($propName, $propValue);
            if (null === $propValue) {
                $nullFields[] = $field;
            }
        }
        if (empty($nullFields)) {
            $nullFields = null;
        }
        $node->setProperty($this->prefix . ':' . $locale . self::NULLFIELDS, $nullFields); // no '-' to avoid nameclashes
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        if ($node->hasProperty($this->prefix . ':' . $locale . self::NULLFIELDS)) {
            $nullFields = $node->getPropertyValue($this->prefix . ':' . $locale . self::NULLFIELDS);
            $nullFields = array_flip($nullFields);
        } else {
            $nullFields = array();
        }
        foreach ($metadata->translatableFields as $field) {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            if (isset($nullFields[$field])) {
                $value = null;
            } elseif ($node->hasProperty($propName)) {
                $value = $node->getPropertyValue($propName);
            } else {
                // Could not find the translation in the given language
                return false;
            }
            $metadata->reflFields[$field]->setValue($document, $value);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($metadata->translatableFields as $field) {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            $prop = $node->getProperty($propName);
            $prop->remove();
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
            if (preg_match('/' . $this->prefix . ':(..)-[^-]*/', $prop->getName(), $matches)) {
                if (is_array($matches) && count($matches) > 1 && !in_array($matches[1], $locales)) {
                    $locales[] = $matches[1];
                }
            }
        }
        return $locales;
    }
}
