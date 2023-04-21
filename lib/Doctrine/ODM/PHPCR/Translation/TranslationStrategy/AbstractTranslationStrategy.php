<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Translation\Translation;

/**
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
abstract class AbstractTranslationStrategy implements TranslationStrategyInterface
{
    /**
     * @var DocumentManagerInterface
     */
    protected $dm;

    /**
     * Prefix to namespace properties or child nodes.
     *
     * @var string
     */
    protected $prefix = Translation::LOCALE_NAMESPACE;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Set the namespace alias for translation extra properties.
     *
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Determine the locale specific property name.
     *
     * @param string $locale
     * @param string $propertyName the untranslated property name
     *
     * @return string the property name with the translation namespace
     */
    public function getTranslatedPropertyName($locale, $propertyName)
    {
        return sprintf('%s:%s-%s', $this->prefix, $locale, $propertyName);
    }

    /**
     * Determine the locale specific property names for an assoc property.
     *
     * @param string $locale
     * @param array  $mapping the mapping for the property
     *
     * @return string the property name with the translation namespace
     */
    public function getTranslatedPropertyNameAssoc($locale, $mapping)
    {
        return [
            'property' => $this->getTranslatedPropertyName($locale, $mapping['property']),
            'assoc' => $this->getTranslatedPropertyName($locale, $mapping['assoc']),
            'assocNulls' => $this->getTranslatedPropertyName($locale, $mapping['assocNulls']),
        ];
    }
}
