<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    PHPCR\NodeInterface;

use Doctrine\ODM\PHPCR\Translation\Translation;

/**
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Daniel Barsotti <daniel.barsotti@liip.ch>
 */
abstract class AbstractTranslationStrategy implements TranslationStrategyInterface
{
    /**
     * Prefix to namespace properties or child nodes
     * @var string
     */
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
     * Get the name of the property where to store the translations of a given property in a given language
     * @param $locale The language to store
     * @param $fieldName The name of the field to translate
     * @return string The name of the property where to store the translation
     */
    protected function getTranslatedPropertyName($locale, $fieldName)
    {
        return sprintf('%s:%s-%s', $this->prefix, $locale, $fieldName);
    }
}
