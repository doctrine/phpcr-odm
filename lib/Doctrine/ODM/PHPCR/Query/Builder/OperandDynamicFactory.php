<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory/node class for dynamic operands.
 *
 * As the name suggests, dynamic operand values change
 * according to the node being compared and are used as
 * "left hand side" (lop) operands in comparisons and
 * in orderings.
 *
 * @IgnoreAnnotation("factoryMethod")
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
     * <code>
     *   $qb->where()
     *     ->gt()
     *       ->lop()->fullTextSearchScore('alias_1')->end()
     *       ->rop()->literal(50)->end()
     *     ->end()
     *
     *   $qb->orderBy()
     *     ->asc()->fullTextSearchScore('alias_1')->end()
     * </code>
     *
     * @factoryMethod
     * @return OperandDynamicFullTextSearchScore
     */
    public function fullTextSearchScore($alias)
    {
        return $this->addChild(new OperandDynamicFullTextSearchScore($this, $alias));
    }

    /**
     * Length operand resolves to length of child operand:
     *
     * <code>
     *   $qb->where()
     *     ->gt()
     *       ->lop()->length('alias_1', 'prop_1')->end()
     *       ->rop()->literal(50)->end()
     *     ->end()
     *
     *   $qb->orderBy()
     *     ->asc()->fullTextSearchScore('alias_1')->end()
     * </code>
     *
     * @factoryMethod
     * @return OperandDynamicLength
     */
    public function length($field)
    {
        return $this->addChild(new OperandDynamicLength($this, $field));
    }

    /**
     * LowerCase operand evaluates to lower-cased string of child operand:
     *
     * <code>
     *   $qb->where()
     *     ->eq()
     *       ->lop()
     *         ->lowerCase()->propertyValue('prop_1', 'alias_1')->end()
     *       ->end()
     *       ->rop()->literal('lower_case')->end()
     *     ->end()
     * </code>
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
     * <code>
     *   $qb->where()
     *     ->eq()
     *       ->lop()
     *         ->upperCase()->propertyValue('prop_1', 'alias_1')->end()
     *       ->end()
     *       ->rop()->literal('UPPER_CASE')->end()
     *     ->end()
     * </code>
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
     * <code>
     *   $qb->where()
     *     ->eq()
     *       ->lop()->documentLocalName('alias_1')->end()
     *       ->rop()->literal('my_node_name')
     *     ->end()
     * </code>
     *
     * Relates to PHPCR NodeLocalNameInterface
     *
     * @factoryMethod
     * @return OperandDynamicLocalName
     */
    public function localName($alias)
    {
        return $this->addChild(new OperandDynamicLocalName($this, $alias));
    }

    /**
     * Resolves to the namespaced
     * name of the node being compared:
     *
     * <code>
     *   $qb->where()
     *     ->eq()
     *       ->lop()->documentName('alias_1')->end()
     *       ->rop()->literal('namespace:my_node_name')
     *     ->end()
     * </code>
     *
     * Relates to PHPCR NodeNameInterface
     *
     * @factoryMethod
     * @return OperandDynamicName
     */
    public function name($alias)
    {
        return $this->addChild(new OperandDynamicName($this, $alias));
    }

    /**
     * Resolves to the value of the specified property
     *
     * <code>
     *   $qb->where()
     *     ->eq()
     *       ->lop()->propertyValue('prop_name', 'alias_1')->end()
     *       ->rop()->literal('my_property_value')
     *     ->end()
     * </code>
     *
     * @factoryMethod
     * @return OperandDynamicField
     */
    public function field($field)
    {
        return $this->addChild(new OperandDynamicField($this, $field));
    }
}

