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
 */
class ChildTranslationStrategy extends AttributeTranslationStrategy
{
    protected $translationNodeName = Translation::LOCALE_NAMESPACE;

    /**
     * {@inheritdoc}
     */
    public function saveTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node);
        parent::saveTranslation($document, $translationNode, $metadata, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node);
        return parent::loadTranslation($document, $translationNode, $metadata, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        $translationNode = $this->getTranslationNode($node);
        parent::removeTranslation($document, $translationNode, $metadata, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $translationNode = $this->getTranslationNode($node);
        $translationNode->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        $translationNode = $this->getTranslationNode($node);
        return parent::getLocalesFor($document, $translationNode, $metadata);
    }

    protected function getTranslationNode(NodeInterface $parentNode)
    {
        if (!$parentNode->hasNode($this->translationNodeName)) {
            $node = $parentNode->addNode($this->translationNodeName);
        } else {
            $node = $parentNode->getNode($this->translationNodeName);
        }

        return $node;
    }
}
