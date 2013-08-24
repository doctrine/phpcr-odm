<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Class which converts a Builder tree to a PHPCR Query
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class BuilderConverterPhpcr
{
    protected $qomf;
    protected $mdf;

    protected $selectorMetadata = array();

    public function __construct(ClassMetadataFactory $mdf, QueryObjectModelFactoryInterface $qomf)
    {
        $this->qomf = $qomf;
        $this->mdf = $mdf;
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
        $from = $builder->getChildrenOfType('From');

        if (!$from) {
            throw new \RuntimeException(
                'No From (source) node in query'
            );
        }

        // dispatch From first
        $this->dispatchMany($builder->getChildrenOfType('From'));

        // dispatch everything else
        $this->dispatchMany($builder->getChildrenOfType('Select'));
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
        foreach ($node->getChildren() as $property) {
            $phpcrName = $this->getPhpcrProperty(
                $property->getSelectorName(),
                $property->getPropertyName()
            );

            $columns[] = $this->qomf->column(
                // do we want to support custom column names in ODM?
                // what do columns get used for in an ODM in anycase?
                $phpcrName,
                $phpcrName
            );
        }

        return $columns;
    }

    public function walkFrom(AbstractNode $node)
    {
        foreach($node->getChildren() as $source) {
            $res = $this->dispatch($source);
        }

        return $res;
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
        $left = $this->dispatch($node->getChildOfType('SourceJoinLeft'));
        $riht = $this->dispatch($node->getChildOfType('SourceJoinRight'));
        $cond = $this->dispatch($node->getChildOfType('SourceJoinCondition'));

        $join = $this->qomf->join($left, $riht, $node->getJoinType(), $cond);

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

    protected function walkSourceJoinCondition(SourceJoinCondition $node)
    {
        foreach ($node->getChildren() as $child) {
            $res = $this->dispatch($child);
        }

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
        $dynOp = $node->getChildOfType('OperandDynamicFactory');
        $staOp = $node->getChildOfType('OperandStaticFactory');
        $phpcrDynOp = $this->dispatch($dynOp);
        $phpcrStaOp = $this->dispatch($staOp);

        $compa = $this->qomf->comparison(
            $phpcrDynOp, $node->getOperator(), $phpcrStaOp
        );

        return $compa;
    }

    protected function walkConstraintNot(ConstraintNot $node)
    {
        $con = $node->getChildOfType('ConstraintInterface');
        $phpcrCon = $this->dispatch($con);

        $ret = $this->qomf->notConstraint(
            $phpcrCon
        );

        return $ret;
    }

    // dynamic operand stuff
    protected function walkOperandDynamicFactory(OperandDynamicFactory $node)
    {
        $op = $node->getChildOfType('OperandDynamicInterface');
        return $this->dispatch($op);
    }

    protected function walkOperandDynamicPropertyValue(OperandDynamicPropertyValue $node)
    {
        $phpcrProperty = $this->getPhpcrProperty(
            $node->getSelectorName(), $node->getPropertyName()
        );

        $op = $this->qomf->propertyValue(
            $node->getSelectorName(), $phpcrProperty
        );

        return $op;
    }

    // static operand stuff
    protected function walkOperandStaticFactory(OperandStaticFactory $node)
    {
        $op = $node->getChildOfType('OperandStaticInterface');
        return $this->dispatch($op);
    }

    protected function walkOperandStaticLiteral(OperandStaticLiteral $node)
    {
        $op = $this->qomf->literal($node->getValue());
        return $op;
    }
}
