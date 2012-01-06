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
    /**
     * {@inheritdoc}
     */
    public function saveTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($metadata->translatableFields as $field) {
            $propName = $this->getTranslatedPropertyName($locale, $field);
            $node->setProperty($propName, $metadata->reflFields[$field]->getValue($document));
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
            $metadata->reflFields[$field]->setValue($document, $node->getPropertyValue($propName));
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
