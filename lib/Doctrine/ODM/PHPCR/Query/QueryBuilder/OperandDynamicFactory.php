<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory/node class for dynamic operands.
 *
 * As the name suggests, dynamic operand values change
 * according to the node being compared and are used as
 * "left hand side" (lop) operands in comparisons and
 * in orderings.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandDynamicFactory extends AbstractNode
{
    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC_FACTORY;
    }

    public function getCardinalityMap()
    {
        return array(
            self::NT_OPERAND_DYNAMIC => array(1, 1),
        );
    }

    /**
     * Full text search score operand:
     *
     *   $qb->where()
     *     ->gt()
     *       ->lop()->fullTextSearchScore('sel_1')->end()
     *       ->rop()->literal(50)->end()
     *     ->end()
     *
     *   $qb->orderBy()
     *     ->ascending()->fullTextSearchScore('sel_1')->end()
     *
     * @factoryMethod
     * @return OperandDynamicFullTextSearchScore
     */
    public function fullTextSearchScore($selectorName)
    {
        return $this->addChild(new OperandDynamicFullTextSearchScore($this, $selectorName));
    }

    /**
     * Length operand resolves to length of child operand:
     *
     *   $qb->where()
     *     ->gt()
     *       ->lop()->length('sel_1', 'prop_1')->end()
     *       ->rop()->literal(50)->end()
     *     ->end()
     *
     *   $qb->orderBy()
     *     ->ascending()->fullTextSearchScore('sel_1')->end()
     *
     * @factoryMethod
     * @return OperandDynamicPropertyValue
     */
    public function length($field)
    {
        return $this->addChild(new OperandDynamicPropertyValue($this, $field));
    }

    /**
     * LowerCase operand evaluates to lower-cased string of child operand:
     *
     *   $qb->where()
     *     ->eq()
     *       ->lop()
     *         ->lowerCase()->propertyValue('prop_1', 'sel_1')->end()
     *       ->end()
     *       ->rop()->literal('lower_case')->end()
     *     ->end()
     *
     * @factoryMethod
     * @return OperandDynamicLowerCase
     */
    public function lowerCase()
    {
        return $this->addChild(new OperandDynamicLowerCase($this));
    }

    /**
     * UpperCase operand evaluates to upper-cased string of child operand:
     *
     *   $qb->where()
     *     ->eq()
     *       ->lop()
     *         ->upperCase()->propertyValue('prop_1', 'sel_1')->end()
     *       ->end()
     *       ->rop()->literal('UPPER_CASE')->end()
     *     ->end()
     *
     * @factoryMethod
     * @return OperandDynamicUpperCase
     */
    public function upperCase()
    {
        return $this->addChild(new OperandDynamicUpperCase($this));
    }

    /**
     * Document local name resolves to the local (non namespaced)
     * name of the node being compared:
     *
     *   $qb->where()
     *     ->eq()
     *       ->lop()->documentLocalName('sel_1')->end()
     *       ->rop()->literal('my_node_name')
     *     ->end()
     *
     * @factoryMethod
     * @return OperandDynamicDocumentLocalName
     */
    public function documentLocalName($selectorName)
    {
        return $this->addChild(new OperandDynamicDocumentLocalName($this, $selectorName));
    }

    /**
     * Resolves to the namespaced
     * name of the node being compared:
     *
     *   $qb->where()
     *     ->eq()
     *       ->lop()->documentName('sel_1')->end()
     *       ->rop()->literal('namespace:my_node_name')
     *     ->end()
     *
     * @factoryMethod
     * @return OperandDynamicDocumentName
     */
    public function documentName($selectorName)
    {
        return $this->addChild(new OperandDynamicDocumentName($this, $selectorName));
    }

    /**
     * Resolves to the value of the specified property
     *
     *   $qb->where()
     *     ->eq()
     *       ->lop()->propertyValue('prop_name', 'sel_1')->end()
     *       ->rop()->literal('my_property_value')
     *     ->end()
     *
     * @factoryMethod
     * @return OperandDynamicPropertyValue
     */
    public function propertyValue($field)
    {
        return $this->addChild(new OperandDynamicPropertyValue($this, $field));
    }
}

