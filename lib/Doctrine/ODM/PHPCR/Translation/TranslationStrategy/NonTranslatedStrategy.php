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
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @link        www.doctrine-project.com
 * @since       1.0
 *
 * @author      David Buchmann <mail@davidbu.ch>
 */
class NonTranslatedStrategy implements TranslationStrategyInterface
{
    /**
     * Identifier of this strategy.
     */
    const NAME = 'none';

    /**
     * @var DocumentManagerInterface
     */
    private $dm;

    /**
     * @param DocumentManagerInterface $dm
     */
    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    /**
     * {@inheritdoc}
     */
    public function saveTranslation(array $data, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        foreach ($data as $field => $propValue) {
            $mapping = $metadata->mappings[$field];
            $propName = $mapping['property'];

            if ($mapping['multivalue'] && $propValue) {
                $propValue = (array) $propValue;
                if (isset($mapping['assoc'])) {
                    $propValue = $this->dm->getUnitOfWork()->processAssoc($node, $mapping, $propValue);
                }
            }

            $node->setProperty($propName, $propValue);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    /**
     * {@inheritdoc}
     *
     * Remove the (untranslated) fields listed in $metadata->translatableFields
     */
    public function removeAllTranslations($document, NodeInterface $node, ClassMetadata $metadata)
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
    public function removeTranslation($document, NodeInterface $node, ClassMetadata $metadata, $locale)
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalesFor($document, NodeInterface $node, ClassMetadata $metadata)
    {
        throw new PHPCRException('This makes no sense with the NonTranslatedStrategy');
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslatedPropertyPath($alias, $propertyName, $locale)
    {
        return [$alias, $propertyName];
    }

    /**
     * {@inheritdoc}
     */
    public function alterQueryForTranslation(
        QueryObjectModelFactoryInterface $qomf,
        SourceInterface &$selector,
        ConstraintInterface &$constraint = null,
        $alias,
        $locale
    ) {
        // nothing to alter
    }
}
