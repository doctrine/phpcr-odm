<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory node for dynamic operands.
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
     * Represents the aliased documents rank by relevance to the full text 
     * search expression given by the "fullTextSearch" constraint.
     *
     * See also: http://www.day.com/specs/jcr/2.0/6_Query.html#FullTextSearchScore
     *
     * <code>
     * $qb->where()
     *   ->gt()
     *     ->fullTextSearchScore('sel_1')
     *     ->literal(50)
     *   ->end()
     *
     * $qb->orderBy()
     *    ->asc()->fullTextSearchScore('sel_1');
     * </code>
     *
     * @param string $alias - Name of alias to use
     *
     * @factoryMethod OperandDynamicFullTextSearchScore
     * @return OperandDynamicFactory
     */
    public function fullTextSearchScore($alias)
    {
        return $this->addChild(new OperandDynamicFullTextSearchScore($this, $alias));
    }

    /**
     * Length operand resolves to length of aliased document.
     *
     * <code>
     * $qb->where()
     *   ->gt()
     *     ->length('alias_1.prop_1')
     *     ->literal(50);
     *
     * $qb->orderBy()->asc()->fullTextSearchScore('sel_1');
     * </code>
     *
     * @param string $field - Name of field to check.
     *
     * @factoryMethod OperandDynamicLength
     * @return OperandDynamicFactory
     */
    public function length($field)
    {
        return $this->addChild(new OperandDynamicLength($this, $field));
    }

    /**
     * LowerCase operand evaluates to lower-cased string of child operand:
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->lowerCase()->field('sel_1.prop_1')->end()
     *     ->literal('lower_case');
     * </code>
     *
     * @factoryMethod OperandDynamicLowerCase
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
     * $qb->where()
     *   ->eq()
     *       ->upperCase()->field('sel_1.prop_1')->end()
     *       ->literal('UPPER_CASE');
     * </code>
     *
     * @factoryMethod OperandDynamicUpperCase
     * @return OperandDynamicUpperCase
     */
    public function upperCase()
    {
        return $this->addChild(new OperandDynamicUpperCase($this));
    }

    /**
     * Document local name evaluates to the local (non namespaced)
     * name of the node being compared.
     *
     * For example, if a node has the path "/path/to/foobar", then "foobar"
     * is the local node name.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->localName('sel_1')
     *     ->literal('my_node_name');
     * </code>
     *
     * Relates to PHPCR NodeLocalNameInterface
     *
     * @param string $alias - Name of alias to use
     *
     * @factoryMethod OperandDynamicLocalName
     * @return OperandDynamicFactory
     */
    public function localName($alias)
    {
        return $this->addChild(new OperandDynamicLocalName($this, $alias));
    }

    /**
     * Evaluates to the namespaced name of the node being compared.
     *
     * For example, if a node has the path "/path/to/bar:foobar", then 
     * "bar:foobar" is the namespaced node name.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->name('sel_1')
     *     ->literal('namespace:my_node_name');
     * </code>
     *
     * Relates to PHPCR NodeNameInterface.
     *
     * @param string $alias - Name of alias to use
     *
     * @factoryMethod OperandDynamicName
     * @return OperandDynamicFactory
     */
    public function name($alias)
    {
        return $this->addChild(new OperandDynamicName($this, $alias));
    }

    /**
     * Evaluates to the value of the specified field.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->field('sel_1.prop_name')
     *     ->literal('my_field_value');
     * </code>
     *
     * @param string $field - name of field to check, including alias name.
     *
     * @factoryMethod OperandDynamicField
     * @return OperandDynamicFactory
     */
    public function field($field)
    {
        return $this->addChild(new OperandDynamicField($this, $field));
    }
}

