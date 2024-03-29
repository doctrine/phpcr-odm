<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Factory node for dynamic operands.
 *
 * @IgnoreAnnotation("factoryMethod")
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandDynamicFactory extends AbstractNode
{
    public function getNodeType(): string
    {
        return self::NT_OPERAND_DYNAMIC_FACTORY;
    }

    public function getCardinalityMap(): array
    {
        return [
            self::NT_OPERAND_DYNAMIC => [1, 1],
        ];
    }

    /**
     * Represents the aliased documents rank by relevance to the full text
     * search expression given by the "fullTextSearch" constraint::.
     *
     * <code>
     * $qb->where()
     *   ->gt()
     *     ->fullTextSearchScore('sel_1')
     *     ->literal(50)
     *   ->end()
     * ->end();
     *
     * $qb->orderBy()
     *   ->asc()->fullTextSearchScore('sel_1')
     * ->end();
     * </code>
     *
     * See also: http://www.day.com/specs/jcr/2.0/6_Query.html#FullTextSearchScore
     *
     * @param string $alias - Name of alias to use
     *
     * @factoryMethod OperandDynamicFullTextSearchScore
     */
    public function fullTextSearchScore(string $alias): OperandDynamicFactory
    {
        return $this->addChild(new OperandDynamicFullTextSearchScore($this, $alias));
    }

    /**
     * Length operand resolves to length of aliased document::.
     *
     * <code>
     * $qb->where()
     *   ->gt()
     *     ->length('alias_1.prop_1')
     *     ->literal(50)
     *   ->end()
     * ->end();
     *
     * $qb->orderBy()->asc()->fullTextSearchScore('sel_1')->end();
     * </code>
     *
     * @param string $field - Name of field to check
     *
     * @factoryMethod OperandDynamicLength
     */
    public function length(string $field): OperandDynamicFactory
    {
        return $this->addChild(new OperandDynamicLength($this, $field));
    }

    /**
     * LowerCase operand evaluates to lower-cased string of child operand::.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->lowerCase()->field('sel_1.prop_1')->end()
     *     ->literal('lower_case')
     *   ->end()
     * ->end();
     * </code>
     *
     * @factoryMethod OperandDynamicLowerCase
     */
    public function lowerCase(): OperandDynamicLowerCase
    {
        return $this->addChild(new OperandDynamicLowerCase($this));
    }

    /**
     * UpperCase operand evaluates to upper-cased string of child operand::.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *       ->upperCase()->field('sel_1.prop_1')->end()
     *       ->literal('UPPER_CASE')
     *   ->end()
     * ->end();
     * </code>
     *
     * @factoryMethod OperandDynamicUpperCase
     */
    public function upperCase(): OperandDynamicUpperCase
    {
        return $this->addChild(new OperandDynamicUpperCase($this));
    }

    /**
     * Document local name evaluates to the local (non namespaced)
     * name of the node being compared.
     *
     * For example, if a node has the path "/path/to/foobar", then "foobar"
     * is the local node name::
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->localName('sel_1')
     *     ->literal('my_node_name')
     *   ->end()
     * ->end();
     * </code>
     *
     * Relates to PHPCR NodeLocalNameInterface
     *
     * @param string $alias - Name of alias to use
     *
     * @factoryMethod OperandDynamicLocalName
     */
    public function localName(string $alias): OperandDynamicFactory
    {
        return $this->addChild(new OperandDynamicLocalName($this, $alias));
    }

    /**
     * Evaluates to the namespaced name of the node being compared.
     *
     * For example, if a node has the path "/path/to/bar:foobar", then
     * "bar:foobar" is the namespaced node name::
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->name('sel_1')
     *     ->literal('namespace:my_node_name')
     *   ->end()
     * ->end();
     * </code>
     *
     * Relates to PHPCR NodeNameInterface.
     *
     * @param string $alias - Name of alias to use
     *
     * @factoryMethod OperandDynamicName
     */
    public function name(string $alias): OperandDynamicFactory
    {
        return $this->addChild(new OperandDynamicName($this, $alias));
    }

    /**
     * Evaluates to the value of the specified field::.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->field('sel_1.prop_name')
     *     ->literal('my_field_value')
     *   ->end()
     * ->end();
     * </code>
     *
     * @param string $field name of field to check, including alias name
     *
     * @factoryMethod OperandDynamicField
     */
    public function field(string $field): OperandDynamicFactory
    {
        return $this->addChild(new OperandDynamicField($this, $field));
    }
}
