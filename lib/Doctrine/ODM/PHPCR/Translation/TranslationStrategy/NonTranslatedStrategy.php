<?php

namespace Doctrine\ODM\PHPCR\Translation\TranslationStrategy;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\ODM\PHPCR\Translation\Translation;
use PHPCR\NodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * A dummy translation strategy for non-translated fields.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class NonTranslatedStrategy implements TranslationStrategyInterface
{
    /**
     * Identifier of this strategy.
     */
    public const NAME = 'none';

    private DocumentManagerInterface $dm;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, ?string $locale): void
    {
        foreach ($data as $field => $propValue) {
            $mapping = $metadata->mappings[$field];
            $propName = $mapping['property'];

            if ($mapping['multivalue'] && $propValue) {
                $propValue = (array) $propValue;
                if (array_key_exists('assoc', $mapping) && null !== $mapping['assoc']) {
                    $propValue = $this->dm->getUnitOfWork()->processAssoc($node, $mapping, $propValue);
                }
            }

            $node->setProperty($propName, $propValue);
        }
    }

    public function loadTranslation(object $document, NodeInterface $node, ClassMetadata $metadata, string $locale): bool
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    /**
     * {@inheritdoc}
     *
     * Remove the (untranslated) fields listed in $metadata->translatableFields
     */
    public function removeAllTranslations(object $document, NodeInterface $node, ClassMetadata $metadata): void
    {
        foreach ($metadata->translatableFields as $field) {
            $mapping = $metadata->mappings[$field];
            $node->setProperty($mapping['property'], null);
        }
    }

    /**
     * {@inheritdoc}
     *
     * This will remove all fields that are now declared as translated
     */
    public function removeTranslation(object $document, NodeInterface $node, ClassMetadata $metadata, string $locale): void
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    public function getLocalesFor(object $document, NodeInterface $node, ClassMetadata $metadata): array
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    public function getTranslatedPropertyPath(string $alias, string $propertyName, string $locale): array
    {
        return [$alias, $propertyName];
    }

    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SourceInterface &$selector,
        ConstraintInterface &$constraint = null,
        string $alias,
        string $locale
    ): void {
        // nothing to alter
    }
}
