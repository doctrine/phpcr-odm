<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Translation\Translation;

/**
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
abstract class AbstractTranslationStrategy implements TranslationStrategyInterface
{
    protected DocumentManagerInterface $dm;

    /**
     * Prefix to namespace properties or child nodes.
     */
    protected string $prefix = Translation::LOCALE_NAMESPACE;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Set the namespace alias for translation extra properties.
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Determine the locale specific property name.
     *
     * @param string $propertyName the untranslated property name
     *
     * @return string the property name with the translation namespace
     */
    public function getTranslatedPropertyName(string $locale, string $propertyName): string
    {
        return sprintf('%s:%s-%s', $this->prefix, $locale, $propertyName);
    }

    /**
     * Determine the locale specific property names for an assoc property.
     *
     * @return array{"property": string, "assoc": string, "assocNulls": string}
     */
    public function getTranslatedPropertyNameAssoc(string $locale, array $mapping): array
    {
        return [
            'property' => $this->getTranslatedPropertyName($locale, $mapping['property']),
            'assoc' => $this->getTranslatedPropertyName($locale, $mapping['assoc']),
            'assocNulls' => $this->getTranslatedPropertyName($locale, $mapping['assocNulls']),
        ];
    }
}
