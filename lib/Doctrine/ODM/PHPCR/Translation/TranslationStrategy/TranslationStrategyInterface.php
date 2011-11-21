<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

interface TranslationStrategyInterface
{
    public function saveTranslations($document, NodeInterface $node, ClassMetadata $metadata, $lang);

    public function loadTranslations($document, NodeInterface $node, ClassMetadata $metadata, $lang);

    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata);

    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $lang);
}
