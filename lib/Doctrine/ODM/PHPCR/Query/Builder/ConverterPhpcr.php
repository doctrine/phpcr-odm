<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use PHPCR\Query\QOM\LiteralInterface;
use PHPCR\Query\QOM\PropertyValueInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SelectorInterface;

/**
 * Class which converts a Builder tree to a PHPCR Query.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConverterPhpcr extends ConverterBase
{
    private QueryObjectModelFactoryInterface $qomf;
    private ClassMetadataFactory $mdf;
    private DocumentManagerInterface $dm;

    /**
     * When document sources are registered we put the document
     * metadata here.
     *
     * @var ClassMetadata[]
     */
    private array $aliasMetadata = [];

    /**
     * When document sources are registered we put the translator
     * here in case the document is translatable.
     *
     * @var TranslationStrategyInterface[]
     */
    private array $translator = [];

    /**
     * Ugly: We need to store the document source types so that we
     * can append constraints to match the phpcr:class and phpcr:classparents
     * later on.
     *
     * @var SourceDocument[]
     */
    private array $sourceDocumentNodes = [];

    /**
     * Used to keep track of which sources are used with translated fields, to
     * tell the translation strategy to update if needed.
     *
     * @var array<string, boolean> keys are the alias, value is true
     */
    private array $aliasWithTranslatedFields;

    private ?string $locale = null;

    public function __construct(
        DocumentManagerInterface $dm,
        QueryObjectModelFactoryInterface $qomf
    ) {
        $this->qomf = $qomf;
        $this->mdf = $dm->getMetadataFactory();
        $this->dm = $dm;
    }

    protected function qomf(): QueryObjectModelFactoryInterface
    {
        return $this->qomf;
    }

    protected function validateAlias(string $alias): string
    {
        if (!array_key_exists($alias, $this->aliasMetadata)) {
            throw new InvalidArgumentException(sprintf(
                'Alias name "%s" is not known. The following aliases '.
                'are valid: "%s"',
                $alias,
                implode(', ', array_keys($this->aliasMetadata))
            ));
        }

        return $alias;
    }

    protected function getPhpcrProperty($originalAlias, $odmField): array
    {
        $this->validateAlias($originalAlias);
        $meta = $this->aliasMetadata[$originalAlias];

        if ($meta->hasField($odmField)) {
            $fieldMeta = $meta->getFieldMapping($odmField);
        } elseif ($meta->hasAssociation($odmField)) {
            $fieldMeta = $meta->getAssociation($odmField);
        } else {
            throw new \Exception(sprintf(
                'Could not find a mapped field or association named "%s" for alias "%s"',
                $odmField,
                $originalAlias
            ));
        }

        $propertyName = $fieldMeta['property'];

        if (empty($fieldMeta['translated'])
            || empty($this->translator[$originalAlias])
        ) {
            return [$originalAlias, $propertyName];
        }

        $propertyPath = $this->translator[$originalAlias]->getTranslatedPropertyPath($originalAlias, $propertyName, $this->locale);

        $this->aliasWithTranslatedFields[$originalAlias] = true;

        return $propertyPath;
    }

    public function getQuery(QueryBuilder $builder): Query
    {
        $this->aliasWithTranslatedFields = [];
        $this->locale = $builder->getLocale();
        if (null === $this->locale && $this->dm->hasLocaleChooserStrategy()) {
            $this->locale = $this->dm->getLocaleChooserStrategy()->getLocale();
        }

        $from = $builder->getChildrenOfType(
            QBConstants::NT_FROM
        );

        if (!$from) {
            throw new RuntimeException(
                'No From (source) node in query'
            );
        }

        $dispatches = [
            QBConstants::NT_FROM,
            QBConstants::NT_SELECT,
            QBConstants::NT_WHERE,
            QBConstants::NT_ORDER_BY,
        ];

        foreach ($dispatches as $dispatchType) {
            $this->dispatchMany($builder->getChildrenOfType($dispatchType));
        }

        if (count($this->sourceDocumentNodes) > 1 && null === $builder->getPrimaryAlias()) {
            throw new InvalidArgumentException(
                'You must specify a primary alias when selecting from multiple document sources'.
                'e.g. $qb->from(\'a\') ...'
            );
        }

        // for each document source add phpcr:{class,classparents} restrictions
        foreach ($this->sourceDocumentNodes as $sourceNode) {
            $documentFqn = $this->aliasMetadata[$sourceNode->getAlias()]->getName();

            $odmClassConstraints = $this->qomf->orConstraint(
                $this->qomf->comparison(
                    $this->qomf->propertyValue(
                        $sourceNode->getAlias(),
                        'phpcr:class'
                    ),
                    QOMConstants::JCR_OPERATOR_EQUAL_TO,
                    $this->qomf->literal($documentFqn)
                ),
                $this->qomf->comparison(
                    $this->qomf->propertyValue(
                        $sourceNode->getAlias(),
                        'phpcr:classparents'
                    ),
                    QOMConstants::JCR_OPERATOR_EQUAL_TO,
                    $this->qomf->literal($documentFqn)
                )
            );

            if ($this->constraint) {
                $this->constraint = $this->qomf->andConstraint(
                    $this->constraint,
                    $odmClassConstraints
                );
            } else {
                $this->constraint = $odmClassConstraints;
            }
        }

        foreach (array_keys($this->aliasWithTranslatedFields) as $alias) {
            $this->translator[$alias]->alterQueryForTranslation($this->qomf, $this->from, $this->constraint, $alias, $this->locale);
        }

        $phpcrQuery = $this->qomf->createQuery(
            $this->from,
            $this->constraint,
            $this->orderings,
            $this->columns
        );

        $query = new Query($phpcrQuery, $this->dm, $builder->getPrimaryAlias());

        if ($firstResult = $builder->getFirstResult()) {
            $query->setFirstResult($firstResult);
        }

        if ($maxResults = $builder->getMaxResults()) {
            $query->setMaxResults($maxResults);
        }

        return $query;
    }

    protected function walkSourceDocument(SourceDocument $node): SelectorInterface
    {
        $alias = $node->getAlias();
        $documentFqn = $node->getDocumentFqn();

        // cache the metadata for this document
        $meta = $this->mdf->getMetadataFor($documentFqn);

        if (null === $meta->getName()) {
            throw new \RuntimeException(sprintf(
                '%s is not a mapped document',
                $documentFqn
            ));
        }

        $this->aliasMetadata[$alias] = $meta;
        if ($this->locale && $meta->translator) {
            $this->translator[$alias] = $this->dm->getTranslationStrategy($meta->translator);
        }
        $nodeType = $meta->getNodeType();

        // make sure we add the phpcr:{class,classparents} constraints
        // unless the document has a unique type; From is dispatched first,
        // so these will always be the primary constraints.
        if (!$meta->hasUniqueNodeType()) {
            $this->sourceDocumentNodes[$alias] = $node;
        }

        // get the PHPCR Alias
        return $this->qomf->selector(
            $alias,
            $nodeType
        );
    }

    protected function walkOperandDynamicField(OperandDynamicField $node): PropertyValueInterface
    {
        $alias = $node->getAlias();
        $field = $node->getField();

        $classMeta = $this->aliasMetadata[$alias];

        if ($field === $classMeta->nodename) {
            throw new InvalidArgumentException(sprintf(
                'Cannot use nodename property "%s" of class "%s" as a dynamic operand use "localname()" instead.',
                $field,
                $classMeta->name
            ));
        }

        if ($classMeta->hasAssociation($field)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot use association property "%s" of class "%s" as a dynamic operand.',
                $field,
                $classMeta->name
            ));
        }

        [$alias, $phpcrProperty] = $this->getPhpcrProperty(
            $alias,
            $field
        );

        $op = $this->qomf->propertyValue(
            $alias,
            $phpcrProperty
        );

        return $op;
    }

    protected function walkOperandStaticLiteral(OperandStaticLiteral $node): LiteralInterface
    {
        $value = $node->getValue();

        if ($field = $node->getParent()?->getChildOfType(AbstractNode::NT_OPERAND_DYNAMIC)) {
            if ($field instanceof OperandDynamicField) {
                $meta = $this->aliasMetadata[$field->getAlias()];
                $fieldMapping = $meta->getFieldMapping($field->getField());
                $type = $fieldMapping['type'];

                $typeMapping = [
                    'string' => 'string',
                    'long' => 'integer',
                    'decimal' => 'string',
                    'boolean' => 'boolean',
                    'name' => 'string',
                    'path' => 'string',
                    'uri' => 'string',
                    'uuid' => 'string',
                ];

                if (array_key_exists($type, $typeMapping)) {
                    settype($value, $typeMapping[$type]);
                }
            }
        }

        return $this->qomf()->literal($value);
    }
}
