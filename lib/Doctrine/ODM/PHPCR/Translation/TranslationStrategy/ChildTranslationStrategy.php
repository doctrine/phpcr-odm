<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

class ChildTranslationStrategy implements TranslationStrategyInterface
{
    public function saveTranslation($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
    {
        throw new \Exception('Not implemented');
    }
}
