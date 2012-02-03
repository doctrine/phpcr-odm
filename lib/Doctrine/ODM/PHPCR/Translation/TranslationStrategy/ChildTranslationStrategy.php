<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    Doctrine\ODM\PHPCR\Translation\Translation,
    PHPCR\NodeInterface;

/**
 * Translation strategy that stores the translations in a child nodes of the current node.
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author      David Buchmann <david@liip.ch>
 */
class ChildTranslationStrategy extends AttributeTranslationStrategy
{
    /**
     * {@inheritdoc}
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node, $locale);
        parent::saveTranslation($data, $translationNode, $metadata, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node, $locale);
        return parent::loadTranslation($document, $translationNode, $metadata, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node, $locale);
        $translationNode->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $locales = $this->getLocalesFor($document, $node, $metadata);
        foreach ($locales as $locale) {
            $this->removeTranslation($document, $node, $metadata, $locale);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $translations = $node->getNodes(Translation::LOCALE_NAMESPACE . ':*');
        $locales = array();
        foreach ($translations as $name => $node) {
            if ($p = strpos($name, ':')) {
                $locales[] = substr($name, $p+1);
            }
        }
        return $locales;
    }

    protected function getTranslationNode(NodeInterface $parentNode, $locale)
    {
        $name = Translation::LOCALE_NAMESPACE . ":$locale";
        if (!$parentNode->hasNode($name)) {
            $node = $parentNode->addNode($name);
        } else {
            $node = $parentNode->getNode($name);
        }

        return $node;
    }

    protected function getTranslatedPropertyName($locale, $fieldName)
    {
        return $fieldName;
    }
}
