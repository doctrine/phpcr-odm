<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\TranslationStrategyInterface;
use PHPCR\Query\QOM\EquiJoinConditionInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;

/**
 * Class which converts a Builder tree to a PHPCR Query
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class BuilderConverterPhpcr
{
    /**
     * @var QueryObjectModelFactoryInterface
     */
    protected $qomf;

    /**
     * @var ClassMetadataFactory
     */
    protected $mdf;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * When document sources are registered we put the document
     * metadata here.
     *
     * @var ClassMetadata[]
     */
    protected $aliasMetadata = array();

    /**
     * When document sources are registered we put the translator
     * here in case the document is translatable.
     *
     * @var TranslationStrategyInterface[]
     */
    protected $translator = array();

    /**
     * Ugly: We need to store the document source types so that we
     * can append constraints to match the phpcr:class and phpcr:classparents
     * later on.
     *
     * @var SourceDocument[]
     */
    protected $sourceDocumentNodes;

    /**
     * Used to keep track of which sources are used with translated fields, to
     * tell the translation strategy to update if needed.
     *
     * @var array keys are the alias, value is true
     */
    protected $aliasWithTranslatedFields;

    /**
     * @var string|null
     */
    protected $locale;

    protected $from = null;
    protected $columns = array();
    protected $orderings = array();
    protected $constraint = null;

    public function __construct(
        DocumentManager $dm,
        QueryObjectModelFactoryInterface $qomf
    ) {
        $this->qomf = $qomf;
        $this->mdf = $dm->getMetadataFactory();
        $this->dm = $dm;
    }

    /**
     * Check that the given alias is valid and return it.
     *
     * This should only be called from the getQuery function AFTER
     * the document sources are known.
     *
     * @param string $alias Alias to validate and return
     *
     * @return string Return the alias to allow this function to be used inline
     *
     * @throws InvalidArgumentException
     */
    protected function validateAlias($alias)
    {
        if (!isset($this->aliasMetadata[$alias])) {
            throw new InvalidArgumentException(sprintf(
                'Alias name "%s" is not known. The following aliases '.
                'are valid: "%s"',
                $alias,
                implode(', ', array_keys($this->aliasMetadata))
            ));
        }

        return $alias;
    }

    /**
     * Return the PHPCR property name and alias for the given ODM document
     * property name and query alias.
     *
     * The alias might change if this is a translated field and the strategy
     * needs to do a join to get in the translation.
     *
     * @param string $originalAlias As specified in the query source.
     * @param string $odmField      Name of ODM document property.
     *
     * @return array first element is the real alias to use, second element is
     *      the property name
     */
    protected function getPhpcrProperty($originalAlias, $odmField)
    {
        $this->validateAlias($originalAlias);
        $meta = $this->aliasMetadata[$originalAlias];;

        if ($meta->hasField($odmField)) {
            $fieldMeta = $meta->getField($odmField);
        } elseif ($meta->hasAssociation($odmField)) {
            $fieldMeta = $meta->getAssociation($odmField);
        } else {
            throw new \Exception(sprintf(
                'Could not find a mapped field or association named "%s" for alias "%s"',
                $odmField, $originalAlias
            ));
        }

        $propertyName = $fieldMeta['property'];

        if (empty($fieldMeta['translated'])
            || empty($this->translator[$originalAlias])
        ) {
            return array($originalAlias, $propertyName);
        }

        $propertyPath = $this->translator[$originalAlias]->getTranslatedPropertyPath($originalAlias, $fieldMeta['property'], $this->locale);

        $this->aliasWithTranslatedFields[$originalAlias] = true;

        return $propertyPath;
    }

    /**
     * Returns an ODM Query object from the given ODM (query) Builder.
     *
     * Dispatches the From, Select, Where and OrderBy nodes. Each of these
     * "root" nodes append or set PHPCR QOM objects to corresponding properties
     * in this class, which are subsequently used to create a PHPCR QOM object which
     * is embedded in an ODM Query object.
     *
     * @param QueryBuilder $builder
     *
     * @return Query
     */
    public function getQuery(QueryBuilder $builder)
    {
        $this->aliasWithTranslatedFields = array();
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

        $dispatches = array(
            QBConstants::NT_FROM,
            QBConstants::NT_SELECT,
            QBConstants::NT_WHERE,
            QBConstants::NT_ORDER_BY,
        );

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

    /**
     * Convenience method to dispatch an array of nodes.
     *
     * @param array
     */
    protected function dispatchMany($nodes)
    {
        foreach ($nodes as $node) {
            $this->dispatch($node);
        }
    }

    /**
     * Dispatch a node.
     *
     * This method will look for a method of the form
     * "walk{NodeType}" in this class and then use that
     * to build the PHPCR QOM counterpart of the given node.
     *
     * @param AbstractNode $node
     *
     * @return object - PHPCR QOM object
     */
    public function dispatch(AbstractNode $node)
    {
        $methodName = sprintf('walk%s', $node->getName());

        if (!method_exists($this, $methodName)) {
            throw new InvalidArgumentException(sprintf(
                'Do not know how to walk node of type "%s"',
                $node->getName()
            ));
        }

        $res = $this->$methodName($node);

        return $res;
    }

    public function walkSelect(AbstractNode $node)
    {
        $columns = array();

        /** @var $property Field */
        foreach ($node->getChildren() as $property) {
            list($alias, $phpcrName) = $this->getPhpcrProperty(
                $property->getAlias(),
                $property->getField()
            );

            $column = $this->qomf->column(
                $alias,
                $phpcrName,
                // do we want to support custom column names in ODM?
                $phpcrName
            );

            $columns[] = $column;
        }

        $this->columns = $columns;

        return $this->columns;
    }

    public function walkSelectAdd(SelectAdd $node)
    {
        $columns = $this->columns;
        $addColumns = $this->walkSelect($node);
        $this->columns = array_merge(
            $columns,
            $addColumns
        );

        return $this->columns;
    }

    public function walkFrom(AbstractNode $node)
    {
        $source = $node->getChild();
        $res = $this->dispatch($source);

        $this->from = $res;

        return $this->from;
    }

    public function walkWhere(Where $where)
    {
        $constraint = $where->getChild();
        $res = $this->dispatch($constraint);
        $this->constraint = $res;

        return $this->constraint;
    }

    public function walkWhereAnd(WhereAnd $whereAnd)
    {
        if (!$this->constraint) {
            return $this->walkWhere($whereAnd);
        }

        $constraint = $whereAnd->getChild();
        $res = $this->dispatch($constraint);
        $newConstraint = $this->qomf->andConstraint(
            $this->constraint,
            $res
        );
        $this->constraint = $newConstraint;

        return $this->constraint;
    }

    public function walkWhereOr(WhereOr $whereOr)
    {
        if (!$this->constraint) {
            return $this->walkWhere($whereOr);
        }

        $constraint = $whereOr->getChild();
        $res = $this->dispatch($constraint);
        $newConstraint = $this->qomf->orConstraint(
            $this->constraint,
            $res
        );
        $this->constraint = $newConstraint;

        return $this->constraint;
    }

    protected function walkSourceDocument(SourceDocument $node)
    {
        $alias = $node->getAlias();
        $documentFqn = $node->getDocumentFqn();

        // make sure we add the phpcr:{class,classparents} constraints
        // From is dispatched first, so these will always be the primary
        // constraints.
        $this->sourceDocumentNodes[$alias] = $node;

        // cache the metadata for this document
        /** @var $meta ClassMetadata */
        $meta = $this->mdf->getMetadataFor($documentFqn);

        if (null === $meta->getName()) {
            throw new \RuntimeException(sprintf(
                '%s is not a mapped document', $documentFqn
            ));
        }

        $this->aliasMetadata[$alias] = $meta;
        if ($this->locale && $meta->translator) {
            $this->translator[$alias] = $this->dm->getTranslationStrategy($meta->translator);
        }
        $nodeType = $meta->getNodeType();

        // get the PHPCR Alias
        $alias = $this->qomf->selector(
            $alias,
            $nodeType
        );

        return $alias;
    }

    protected function walkSourceJoin(SourceJoin $node)
    {
        $left = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_LEFT));
        $right = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_RIGHT));
        $cond = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_CONDITION_FACTORY));

        $join = $this->qomf->join($left, $right, $node->getJoinType(), $cond);

        return $join;
    }

    protected function walkSourceJoinLeft(SourceJoinLeft $node)
    {
        $left = $this->walkFrom($node);
        return $left;
    }

    protected function walkSourceJoinRight(SourceJoinRight $node)
    {
        $right = $this->walkFrom($node);
        return $right;
    }

    protected function walkSourceJoinConditionFactory(SourceJoinConditionFactory $node)
    {
        $res = $this->dispatch($node->getChild());

        return $res;
    }

    /**
     * @param SourceJoinConditionEqui $node
     *
     * @return EquiJoinConditionInterface
     */
    protected function walkSourceJoinConditionEqui(SourceJoinConditionEqui $node)
    {
        list($alias1, $phpcrProperty1) = $this->getPhpcrProperty(
            $node->getAlias1(), $node->getProperty1()
        );
        list($alias2, $phpcrProperty2) = $this->getPhpcrProperty(
            $node->getAlias2(), $node->getProperty2()
        );

        $equi = $this->qomf->equiJoinCondition(
            $alias1, $phpcrProperty1,
            $alias2, $phpcrProperty2
        );

        return $equi;
    }

    protected function walkSourceJoinConditionDescendant(SourceJoinConditionDescendant $node)
    {
        $joinCon = $this->qomf->descendantNodeJoinCondition(
            $node->getDescendantAlias(),
            $node->getAncestorAlias()
        );
        return $joinCon;
    }

    protected function walkSourceJoinConditionChildDocument(SourceJoinConditionChildDocument $node)
    {
        $joinCon = $this->qomf->childNodeJoinCondition(
            $node->getChildAlias(),
            $node->getParentAlias()
        );

        return $joinCon;
    }

    protected function walkSourceJoinConditionSameDocument(SourceJoinConditionSameDocument $node)
    {
        $joinCon = $this->qomf->childNodeJoinCondition(
            $this->validateAlias($node->getAlias1Name()),
            $this->validateAlias($node->getAlias2Name()),
            $node->getAlias2Path()
        );
        return $joinCon;
    }

    protected function doWalkConstraintComposite(AbstractNode $node, $method)
    {
        $children = $node->getChildren();

        if (count($children) == 1) {
            $op = $this->dispatch(current($children));
            return $op;
        }

        $lConstraint = array_shift($children);
        $lPhpcrConstraint = $this->dispatch($lConstraint);

        foreach ($children as $rConstraint) {
            $rPhpcrConstraint = $this->dispatch($rConstraint);
            $phpcrComposite = $this->qomf->$method($lPhpcrConstraint, $rPhpcrConstraint);

            $lPhpcrConstraint = $phpcrComposite;
        }

        return $phpcrComposite;
    }

    protected function walkConstraintAndX(ConstraintAndX $node)
    {
        return $this->doWalkConstraintComposite($node, 'andConstraint');
    }

    protected function walkConstraintOrX(ConstraintOrX $node)
    {
        return $this->doWalkConstraintComposite($node, 'orConstraint');
    }

    protected function walkConstraintFieldIsset(ConstraintFieldIsset $node)
    {
        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $node->getAlias(), $node->getField()
        );

        $con = $this->qomf->propertyExistence(
            $alias,
            $phpcrProperty
        );

        return $con;
    }

    protected function walkConstraintFullTextSearch(ConstraintFullTextSearch $node)
    {
        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $node->getAlias(), $node->getField()
        );

        $con = $this->qomf->fullTextSearch(
            $alias,
            $phpcrProperty,
            $node->getFullTextSearchExpression()
        );

        return $con;
    }

    protected function walkConstraintSame(ConstraintSame $node)
    {
        $con = $this->qomf->sameNode(
            $this->validateAlias($node->getAlias()),
            $node->getPath()
        );

        return $con;
    }

    protected function walkConstraintDescendant(ConstraintDescendant $node)
    {
        $con = $this->qomf->descendantNode(
            $this->validateAlias($node->getAlias()),
            $node->getAncestorPath()
        );

        return $con;
    }

    protected function walkConstraintChild(ConstraintChild $node)
    {
        $con = $this->qomf->childNode(
            $this->validateAlias($node->getAlias()),
            $node->getParentPath()
        );

        return $con;
    }

    protected function walkConstraintComparison(ConstraintComparison $node)
    {
        $dynOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );
        $statOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_STATIC
        );

        $phpcrDynOp = $this->dispatch($dynOp);
        $phpcrStatOp = $this->dispatch($statOp);

        $compa = $this->qomf->comparison(
            $phpcrDynOp, $node->getOperator(), $phpcrStatOp
        );

        return $compa;
    }

    protected function walkConstraintNot(ConstraintNot $node)
    {
        $con = $node->getChildOfType(
            QBConstants::NT_CONSTRAINT
        );

        $phpcrCon = $this->dispatch($con);

        $ret = $this->qomf->notConstraint(
            $phpcrCon
        );

        return $ret;
    }

    protected function walkOperandDynamicField(OperandDynamicField $node)
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

        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $alias,
            $field
        );

        $op = $this->qomf->propertyValue(
            $alias,
            $phpcrProperty
        );

        return $op;
    }

    protected function walkOperandDynamicLocalName(OperandDynamicLocalName $node)
    {
        $op = $this->qomf->nodeLocalName(
            $this->validateAlias($node->getAlias())
        );

        return $op;
    }

    protected function walkOperandDynamicFullTextSearchScore(OperandDynamicFullTextSearchScore $node)
    {
        $op = $this->qomf->fullTextSearchScore(
            $this->validateAlias($node->getAlias())
        );

        return $op;
    }

    protected function walkOperandDynamicLength(OperandDynamicLength $node)
    {
        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $node->getAlias(),
            $node->getField()
        );

        $propertyValue = $this->qomf->propertyValue(
            $alias,
            $phpcrProperty
        );

        $op = $this->qomf->length(
            $propertyValue
        );

        return $op;
    }

    protected function walkOperandDynamicName(OperandDynamicName $node)
    {
        $op = $this->qomf->nodeName(
            $this->validateAlias($node->getAlias())
        );

        return $op;
    }

    protected function walkOperandDynamicLowerCase(OperandDynamicLowerCase $node)
    {
        $child = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );

        $phpcrChild = $this->dispatch($child);

        $op = $this->qomf->lowerCase(
            $phpcrChild
        );

        return $op;
    }

    protected function walkOperandDynamicUpperCase(OperandDynamicUpperCase $node)
    {
        $child = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );

        $phpcrChild = $this->dispatch($child);

        $op = $this->qomf->upperCase(
            $phpcrChild
        );

        return $op;
    }

    protected function walkOperandStaticLiteral(OperandStaticLiteral $node)
    {
        $op = $this->qomf->literal($node->getValue());
        return $op;
    }

    protected function walkOperandStaticParameter(OperandStaticParameter $node)
    {
        $op = $this->qomf->bindVariable($node->getVariableName());
        return $op;
    }

    // ordering
    protected function walkOrderBy(OrderBy $node)
    {
        $this->orderings = array();

        $orderings = $node->getChildren();

        /** @var $ordering Ordering */
        foreach ($orderings as $ordering) {
            $dynOp = $ordering->getChildOfType(
                QBConstants::NT_OPERAND_DYNAMIC
            );

            $phpcrDynOp = $this->dispatch($dynOp);

            if ($ordering->getOrder() == QOMConstants::JCR_ORDER_ASCENDING) {
                $ordering = $this->qomf->ascending($phpcrDynOp);
            } else {
                $ordering = $this->qomf->descending($phpcrDynOp);
            }

            $this->orderings[] = $ordering;
        }

        return $this->orderings;
    }

    protected function walkOrderByAdd(OrderBy $node)
    {
        $this->orderings = array_merge(
            $this->orderings,
            $this->walkOrderBy($node)
        );

        return $this->orderings;
    }
}
