<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

class AttributeTranslationStrategy //implements TranslationStrategyInterface
{
    protected $prefix = 'lang';

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function saveTranslations($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        foreach ($metadata->translatableFields as $field) {

            $propName = $this->getTranslatedPropertyName($lang, $field);
            $node->setProperty($propName, $document->$field);
        }
    }

    public function loadTranslations($document, NodeInterface $node, ClassMetadata $metadata, $lang)
    {
        // TODO: lang could be null...
        foreach ($metadata->translatableFields as $field) {
            $propName = $this->getTranslatedPropertyName($lang, $field);
            $document->$field = $node->getPropertyValue($propName);
        }
    }

    protected function getTranslatedPropertyName($lang, $fieldName)
    {
        return sprintf('%s-%s-%s', $this->prefix, $lang, $fieldName);
    }
}
