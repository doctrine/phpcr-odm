<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\RuntimeException;
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
     * @var PHPCR\Query\QOM\QueryObjectModelFactoryInterface
     */
    protected $qomf;

    /**
     * @var Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory
     */
    protected $mdf;

    /**
     * @var Doctrine\ODM\PHPCR\DocumentManager
     */
    protected $dm;

    /**
     * When document sources are registered we put the document
     * metadata here.
     *
     * @var array
     */
    protected $selectorMetadata = array();

    /**
     * When document sources are registered we put the translator
     * here in case the document is translatable.
     *
     * @var array
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

    protected function getMetadata($alias)
    {
        if (!isset($this->selectorMetadata[$alias])) {
            throw new RuntimeException(sprintf(
                'Selector name "%s" has not known. The following selectors '.
                'are valid: "%s"',
                $alias,
                implode(', ', array_keys($this->selectorMetadata))
            ));
        }

        return $this->selectorMetadata[$alias];
    }

    /**
     * Return the PHPCR property name for the given ODM document property name
     *
     * @param string $alias - Name of selector (corresponds to document source)
     * @param string $odmField - Name of ODM document property
     *
     * @return string
     */
    protected function getPhpcrProperty($alias, $odmField)
    {
        $fieldMeta = $this->getMetadata($alias)->getField($odmField);
        $property = $fieldMeta['property'];

        if (!empty($this->translator[$alias]) && !empty($fieldMeta['translated'])) {
            $property = $this->translator[$alias]->getTranslatedPropertyName($this->locale, $fieldMeta['property']);
        }

        return $property;
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
     * @return Doctrine\ODM\PHPCR\Query\Query
     */
    public function getQuery(QueryBuilder $builder)
    {
        $this->locale = $builder->getLocale();
        if (null === $this->locale && $this->dm->hasLocaleChooserStrategy()) {
            $this->locale = $this->dm->getLocaleChooserStrategy()->getDefaultLocale();
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

        // for each document source add phpcr:{class,classparents} restrictions
        foreach ($this->sourceDocumentNodes as $sourceNode) {
            $odmClassConstraints = $this->qomf->orConstraint(
                $this->qomf->comparison(
                    $this->qomf->propertyValue(
                        $sourceNode->getAlias(),
                        'phpcr:class'
                    ),
                    QOMConstants::JCR_OPERATOR_EQUAL_TO,
                    $this->qomf->literal($sourceNode->getDocumentFqn())
                ),
                $this->qomf->comparison(
                    $this->qomf->propertyValue(
                        $sourceNode->getAlias(),
                        'phpcr:classparents'
                    ),
                    QOMConstants::JCR_OPERATOR_EQUAL_TO,
                    $this->qomf->literal($sourceNode->getDocumentFqn())
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

        $phpcrQuery = $this->qomf->createQuery(
            $this->from,
            $this->constraint,
            $this->orderings,
            $this->columns
        );

        $this->query = new Query($phpcrQuery, $this->dm);

        if ($firstResult = $builder->getFirstResult()) {
            $this->query->setFirstResult($firstResult);
        }

        if ($maxResults = $builder->getMaxResults()) {
            $this->query->setMaxResults($maxResults);
        }

        return $this->query;
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

    public function walkSelect($node)
    {
        $columns = array();

        foreach ($node->getChildren() as $property) {
            $phpcrName = $this->getPhpcrProperty(
                $property->getAlias(),
                $property->getField()
            );

            $column = $this->qomf->column(
                $property->getAlias(),
                // do we want to support custom column names in ODM?
                // what do columns get used for in an ODM in anycase?
                $phpcrName,
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
        // make sure we add the phpcr:{class,classparents} constraints
        // From is dispatched first, so these will always be the primary
        // constraints.
        $this->sourceDocumentNodes[$alias] = $node;

        // index the metadata for this document
        $meta = $this->mdf->getMetadataFor($node->getDocumentFqn());
        $this->selectorMetadata[$alias] = $meta;
        if ($this->locale && 'attribute' === $meta->translator) {
            $this->translator[$alias] = $this->dm->getTranslationStrategy($meta->translator);
        }

        // get the PHPCR Selector
        $selector = $this->qomf->selector(
            $alias,
            $meta->getNodeType()
        );

        return $selector;
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

    protected function walkSourceJoinConditionEqui(SourceJoinConditionEqui $node)
    {
        $phpcrProperty1 = $this->getPhpcrProperty(
            $node->getSelector1(), $node->getProperty1()
        );
        $phpcrProperty2 = $this->getPhpcrProperty(
            $node->getSelector2(), $node->getProperty2()
        );

        $equi = $this->qomf->equiJoinCondition(
            $node->getSelector1(), $phpcrProperty1,
            $node->getSelector2(), $phpcrProperty2
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
            $node->getSelector1Name(),
            $node->getSelector2Name(),
            $node->getSelector2Path()
        );
        return $joinCon;
    }

    protected function doWalkConstraintComposite($node, $method)
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
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getAlias(), $node->getField()
        );

        $con = $this->qomf->propertyExistence(
            $node->getAlias(),
            $phpcrProperty
        );

        return $con;
    }

    protected function walkConstraintFullTextSearch(ConstraintFullTextSearch $node)
    {
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getAlias(), $node->getField()
        );

        $con = $this->qomf->fullTextSearch(
            $node->getAlias(),
            $phpcrProperty,
            $node->getFullTextSearchExpression()
        );

        return $con;
    }

    protected function walkConstraintSame(ConstraintSame $node)
    {
        $con = $this->qomf->sameNode(
            $node->getAlias(),
            $node->getPath()
        );

        return $con;
    }

    protected function walkConstraintDescendant(ConstraintDescendant $node)
    {
        $con = $this->qomf->descendantNode(
            $node->getAlias(),
            $node->getAncestorPath()
        );

        return $con;
    }

    protected function walkConstraintChild(ConstraintChild $node)
    {
        $con = $this->qomf->childNode(
            $node->getAlias(),
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
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getAlias(),
            $node->getField()
        );

        $op = $this->qomf->propertyValue(
            $node->getAlias(),
            $phpcrProperty
        );

        return $op;
    }

    protected function walkOperandDynamicLocalName(OperandDynamicLocalName $node)
    {
        $op = $this->qomf->nodeLocalName(
            $node->getAlias()
        );

        return $op;
    }

    protected function walkOperandDynamicFullTextSearchScore(OperandDynamicFullTextSearchScore $node)
    {
        $op = $this->qomf->fullTextSearchScore(
            $node->getAlias()
        );

        return $op;
    }

    protected function walkOperandDynamicLength(OperandDynamicLength $node)
    {
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getAlias(),
            $node->getField()
        );

        $propertyValue = $this->qomf->propertyValue(
            $node->getAlias(),
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
            $node->getAlias()
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
