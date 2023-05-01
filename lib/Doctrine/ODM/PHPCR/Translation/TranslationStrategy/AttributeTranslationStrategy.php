<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\ODM\PHPCR\Translation\Translation;
use PHPCR\NodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * Translation strategy that stores the translations in attributes of the same node.
 *
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 * @author David Buchmann <david@liip.ch>
 */
class AttributeTranslationStrategy extends AbstractTranslationStrategy
{
    /**
     * Identifier of this strategy.
     */
    public const NAME = 'attribute';

    public const NULLFIELDS = 'nullfields';

    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, ?string $locale): void
    {
        if (null === $locale) {
            throw new PHPCRException('locale may not be null');
        }
        // no need to validate non-nullable condition, the UoW does that for all fields
        $nullFields = [];
        foreach ($data as $field => $propValue) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);

            if ($mapping['multivalue'] && $propValue) {
                $propValue = (array) $propValue;
                if (array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                    $transMapping = $this->getTranslatedPropertyNameAssoc($locale, $mapping);
                    $propValue = $this->dm->getUnitOfWork()->processAssoc($node, $transMapping, $propValue);
                }
            }

            $node->setProperty($propName, $propValue);

            if (null === $propValue) {
                $nullFields[] = $mapping['property'];
            }
        }
        if (empty($nullFields)) {
            $nullFields = null;
        }
        $node->setProperty($this->prefix.':'.$locale.self::NULLFIELDS, $nullFields); // no '-' to avoid name clashes
    }

    /**
     * Helper method to detect if there is any translated field at all, to
     * not null all fields if the locale does not exist.
     */
    private function checkHasFields(NodeInterface $node, ClassMetadata $metadata, string $locale): bool
    {
        if ($node->hasProperty($this->prefix.':'.$locale.self::NULLFIELDS)) {
            return true;
        }

        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);
            if ($node->hasProperty($propName)) {
                return true;
            }
        }

        return false;
    }

    public function loadTranslation(object $document, NodeInterface $node, ClassMetadata $metadata, string $locale): bool
    {
        if (!$this->checkHasFields($node, $metadata, $locale)) {
            return false;
        }

        $properties = $node->getPropertiesValues(null, false);

        // we have a translation, now update the document fields
        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);
            if ($node->hasProperty($propName)) {
                $value = $node->getPropertyValue($propName);
                if (true === $mapping['multivalue']) {
                    if (array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                        $transMapping = $this->getTranslatedPropertyNameAssoc($locale, $mapping);
                        $value = $this->dm->getUnitOfWork()->createAssoc($properties, $transMapping);
                    } else {
                        $value = (array) $value;
                    }
                }
            } else {
                // A null field or a missing field
                $value = ($metadata->mappings[$field]['multivalue']) ? [] : null;
            }

            $metadata->reflFields[$field]->setValue($document, $value);
        }

        return true;
    }

    public function removeTranslation(object $document, NodeInterface $node, ClassMetadata $metadata, string $locale): void
    {
        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $propName = $this->getTranslatedPropertyName($locale, $mapping['property']);

            if ($node->hasProperty($propName)) {
                $prop = $node->getProperty($propName);
                $prop->remove();

                $mapping = $metadata->mappings[$field];
                if (true === $mapping['multivalue'] && array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                    $transMapping = $this->getTranslatedPropertyNameAssoc($locale, $mapping);
                    $this->dm->getUnitOfWork()->removeAssoc($node, $transMapping);
                }
            }
        }

        if ($node->hasProperty($this->prefix.':'.$locale.self::NULLFIELDS)) {
            $node->setProperty($this->prefix.':'.$locale.self::NULLFIELDS, null);
        }
    }

    public function removeAllTranslations(object $document, NodeInterface $node, ClassMetadata $metadata): void
    {
        foreach ($this->getLocalesFor($document, $node, $metadata) as $locale) {
            foreach ($metadata->translatableFields as $field) {
                $node->setProperty(
                    $this->getTranslatedPropertyName($locale, $metadata->mappings[$field]['property']),
                    null
                );
            }
        }
    }

    public function getLocalesFor(object $document, NodeInterface $node, ClassMetadata $metadata): array
    {
        $locales = [];
        foreach ($node->getProperties($this->prefix.':*') as $prop) {
            $matches = null;
            if (preg_match('/'.$this->prefix.':([a-zA-Z1-9_]+)-/', $prop->getName(), $matches)) {
                if (is_array($matches) && count($matches) > 1 && !in_array($matches[1], $locales, true)) {
                    $locales[] = $matches[1];
                }
            }
        }

        return $locales;
    }

    /**
     * Translated properties are on the same node, but have a different name.
     */
    public function getTranslatedPropertyPath(string $alias, string $propertyName, string $locale): array
    {
        return [$alias, $this->getTranslatedPropertyName($locale, $propertyName)];
    }

    /**
     * Nothing to do, the properties are on the same node.
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SourceInterface &$selector,
        ConstraintInterface &$constraint = null,
        string $alias,
        string $locale
    ): void {
        // do nothing
    }
}
