<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode as QBConstants;

/**
 * Class which converts a Builder tree to a PHPCR Query
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class BuilderConverterPhpcr
{
    protected $qomf;
    protected $mdf;
    protected $dm;

    protected $selectorMetadata = array();

    protected $from = null;
    protected $columns = array();
    protected $orderings = array();
    protected $where = null;

    public function __construct(DocumentManager $dm, QueryObjectModelFactoryInterface $qomf)
    {
        $this->qomf = $qomf;
        $this->mdf = $dm->getMetadataFactory();
        $this->dm = $dm;
    }

    protected function getMetadata($selectorName)
    {
        if (!isset($this->selectorMetadata[$selectorName])) {
            throw new \RuntimeException(sprintf(
                'Selector name "%s" has not known. The following selectors '.
                'are valid: "%s"',
                $selectorName,
                implode(array_keys($this->selectorMetadata))
            ));
        }

        return $this->selectorMetadata[$selectorName];
    }

    protected function getFieldMapping($selectorName, $propertyName)
    {
        $fieldMeta = $this->getMetadata($selectorName)
            ->getField($propertyName);

        return $fieldMeta;
    }

    protected function getPhpcrProperty($selectorName, $odmPropertyName)
    {
        $fieldMeta = $this->getFieldMapping($selectorName, $odmPropertyName);
        return $fieldMeta['property'];
    }

    public function getQuery(Builder $builder)
    {
        $from = $builder->getChildrenOfType(
            QBConstants::NT_FROM
        );

        if (!$from) {
            throw new \RuntimeException(
                'No From (source) node in query'
            );
        }

        // dispatch From first
        $this->dispatchMany($from);

        // dispatch everything else
        $this->dispatchMany($builder->getChildrenOfType(QBConstants::NT_SELECT));
        $this->dispatchMany($builder->getChildrenOfType(QBConstants::NT_WHERE));
        $this->dispatchMany($builder->getChildrenOfType(QBConstants::NT_ORDER_BY));

        $phpcrQuery = $this->qomf->createQuery(
            $this->from,
            $this->where,
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

    public function dispatchMany($nodes)
    {
        foreach ($nodes as $node) {
            $this->dispatch($node);
        }
    }

    public function dispatch(AbstractNode $node)
    {
        $methodName = sprintf('walk%s', $node->getName());

        if (!method_exists($this, $methodName)) {
            throw new \InvalidArgumentException(sprintf(
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
                $property->getSelectorName(),
                $property->getPropertyName()
            );

            $column = $this->qomf->column(
                // do we want to support custom column names in ODM?
                // what do columns get used for in an ODM in anycase?
                $phpcrName,
                $phpcrName
            );

            $this->columns[] = $column;
        }

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
        // note: only supporting "one" where constraint atm (no aggregation)
        $constraint = $where->getChild();
        $res = $this->dispatch($constraint);
        $this->where = $res;

        return $this->where;
    }

    protected function walkSourceDocument(SourceDocument $node)
    {
        // make sure we add the phpcr:{class,classparents} constraints
        // From is dispatched first, so these will always be the primary
        // constraints.
        $this->constraints[] = $this->qomf->orConstraint(
            $this->qomf->comparison(
                $this->qomf->propertyValue('phpcr:class'),
                QOMConstants::JCR_OPERATOR_EQUAL_TO,
                $this->qomf->literal($node->getDocumentFqn())
            ),
            $this->qomf->comparison(
                $this->qomf->propertyValue('phpcr:classparents'),
                QOMConstants::JCR_OPERATOR_EQUAL_TO,
                $this->qomf->literal($node->getDocumentFqn())
            )
        );

        // index the metadata for this document
        $meta = $this->mdf->getMetadataFor($node->getDocumentFqn());
        $this->selectorMetadata[$node->getSelectorName()] = $meta;

        // get the PHPCR Selector
        $selector = $this->qomf->selector(
            $meta->getNodeType(),
            $node->getSelectorName()
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
            $node->getDescendantSelectorName(),
            $node->getAncestorSelectorName()
        );
        return $joinCon;
    }

    protected function walkSourceJoinConditionChildDocument(SourceJoinConditionChildDocument $node)
    {
        $joinCon = $this->qomf->childNodeJoinCondition(
            $node->getChildSelectorName(),
            $node->getParentSelectorName()
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

    protected function walkConstraintAndX(ConstraintAndX $node)
    {
        list($lopNode, $ropNode) = $node->getChildren();
        $lop = $this->dispatch($lopNode);
        $rop = $this->dispatch($ropNode);

        $composite = $this->qomf->andConstraint($lop, $rop);

        return $composite;
    }

    protected function walkConstraintOrX(ConstraintOrX $node)
    {
        list($lopNode, $ropNode) = $node->getChildren();

        $lop = $this->dispatch($lopNode);
        $rop = $this->dispatch($ropNode);

        $composite = $this->qomf->orConstraint($lop, $rop);

        return $composite;
    }

    protected function walkConstraintPropertyExists(ConstraintPropertyExists $node)
    {
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getSelectorName(), $node->getPropertyName()
        );

        $con = $this->qomf->propertyExistence(
            $phpcrProperty,
            $node->getSelectorName()
        );

        return $con;
    }

    protected function walkConstraintFullTextSearch(ConstraintFullTextSearch $node)
    {
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getSelectorName(), $node->getPropertyName()
        );

        $con = $this->qomf->fullTextSearch(
            $phpcrProperty,
            $node->getFullTextSearchExpression(),
            $node->getSelectorName()
        );

        return $con;
    }

    protected function walkConstraintSameDocument(ConstraintSameDocument $node)
    {
        $con = $this->qomf->sameNode(
            '/path/to',
            'sel_1'
        );

        return $con;
    }

    protected function walkConstraintDescendantDocument(ConstraintDescendantDocument $node)
    {
        $con = $this->qomf->descendantNode(
            '/path/to',
            'sel_1'
        );

        return $con;
    }

    protected function walkConstraintChildDocument(ConstraintChildDocument $node)
    {
        $con = $this->qomf->childNode(
            '/path/to/parent',
            'sel_1'
        );

        return $con;
    }

    protected function walkConstraintComparison(ConstraintComparison $node)
    {
        $dynOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC_FACTORY
        );
        $statOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_STATIC_FACTORY
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

    // dynamic operand stuff
    protected function walkOperandDynamicFactory(OperandDynamicFactory $node)
    {
        $op = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );

        return $this->dispatch($op);
    }

    protected function walkOperandDynamicPropertyValue(OperandDynamicPropertyValue $node)
    {
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getSelectorName(), $node->getPropertyName()
        );

        $op = $this->qomf->propertyValue(
            $phpcrProperty, $node->getSelectorName()
        );

        return $op;
    }

    protected function walkOperandDynamicDocumentLocalName(OperandDynamicDocumentLocalName $node)
    {
        $op = $this->qomf->nodeLocalName(
            $node->getSelectorName()
        );

        return $op;
    }

    protected function walkOperandDynamicFullTextSearchScore(OperandDynamicFullTextSearchScore $node)
    {
        $op = $this->qomf->fullTextSearchScore(
            $node->getSelectorName()
        );

        return $op;
    }

    protected function walkOperandDynamicLength(OperandDynamicLength $node)
    {
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getSelectorName(), $node->getPropertyName()
        );

        $propertyValue = $this->qomf->propertyValue(
            $phpcrProperty, $node->getSelectorName()
        );

        $op = $this->qomf->length(
            $propertyValue
        );

        return $op;
    }

    protected function walkOperandDynamicDocumentName(OperandDynamicDocumentName $node)
    {
        $op = $this->qomf->nodeName(
            $node->getSelectorName()
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

    // static operand stuff
    protected function walkOperandStaticFactory(OperandStaticFactory $node)
    {
        $op = $node->getChildOfType(
            QBConstants::NT_OPERAND_STATIC
        );

        return $this->dispatch($op);
    }

    protected function walkOperandStaticLiteral(OperandStaticLiteral $node)
    {
        $op = $this->qomf->literal($node->getValue());
        return $op;
    }

    protected function walkOperandStaticBindVariable(OperandStaticBindVariable $node)
    {
        $op = $this->qomf->bindVariable($node->getVariableName());
        return $op;
    }

    // ordering
    protected function walkOrderBy(OrderBy $node)
    {
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
}
