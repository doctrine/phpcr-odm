<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use PHPCR\NodeInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AbstractTranslationStrategy;

use Doctrine\Tests\ODM\PHPCR\PHPCRTestCase;

class AbstractTranslationStrategyTest extends PHPCRTestCase
{
    public function testSetPrefixAndGetPropertyName()
    {
        $s = new Strategy();
        $s->setPrefix('test');
        $name = $s->getTranslatedPropertyName('fr', 'field');
        $this->assertEquals('test:fr-field', $name);
    }

}

/**
 * dummy strategy implementing all methods so it can be instantiated
 */
class Strategy extends AbstractTranslationStrategy
{
    function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale) {}
    function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale) {}
    function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata) {}
    function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale) {}
    function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata) {}
    public function getTranslatedPropertyName($locale, $fieldName)
    {
        return parent::getTranslatedPropertyName($locale, $fieldName);
    }
}