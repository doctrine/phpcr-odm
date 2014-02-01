<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;
use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Exception\OutOfBoundsException;

/**
 * All QueryBuilder nodes extend this class.
 *
 * Each query builder node must declare its node type
 * (one of the NT_* constants declared below) and provide
 * a cardinality map describing how many of each type of nodes
 * are allowed to be added.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class AbstractNode
{
    const NT_BUILDER = 'builder';
    const NT_CONSTRAINT = 'constraint';
    const NT_CONSTRAINT_FACTORY = 'constraint_factory';
    const NT_FROM = 'from';
    const NT_OPERAND_DYNAMIC = 'operand_dynamic';
    const NT_OPERAND_DYNAMIC_FACTORY = 'operand_dynamic_factory';
    const NT_OPERAND_STATIC = 'operand_static';
    const NT_OPERAND_FACTORY = 'operand_static_factory';
    const NT_ORDERING = 'ordering';
    const NT_ORDER_BY = 'order_by';
    const NT_PROPERTY = 'property';
    const NT_SELECT = 'select';
    const NT_SOURCE = 'source';
    const NT_SOURCE_FACTORY = 'source_factory';
    const NT_SOURCE_JOIN_CONDITION = 'source_join_condition';
    const NT_SOURCE_JOIN_CONDITION_FACTORY = 'source_join_condition_factory';
    const NT_SOURCE_JOIN_LEFT = 'source_join_left';
    const NT_SOURCE_JOIN_RIGHT = 'source_join_right';
    const NT_WHERE = 'where';
    const NT_WHERE_AND = 'where_and';
    const NT_WHERE_OR = 'where_or';

    protected $children = array();
    protected $parent;

    public function __construct(AbstractNode $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Return the type of node.
     *
     * Must be one of self::NT_*
     *
     * @return string
     */
    abstract public function getNodeType();

    /**
     * Return the parent of this node.
     *
     * @return AbstractNode
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return the last part of the this classes FQN (i.e. the basename).
     *
     * <strike>This should only be used when generating exceptions</strike>
     * This is also used to determine the dispatching method -- should it be?
     *
     * @return string
     */
    final public function getName()
    {
        $refl = new \ReflectionClass($this);
        $short = $refl->getShortName();

        return $short;
    }

    /**
     * Return the cardinality map for this node.
     *
     * e.g.
     *     array(
     *         self::NT_JOIN_CONDITION => array(1, 1), // require exactly 1 join condition
     *         self::NT_SOURCE => array(2, 2), // exactly 2 sources
     *     );
     *
     * or:
     *     array(
     *         self::NT_PROPERTY => array(1, null), // require one to many Columns
     *     );
     *
     * or:
     *     array(
     *         self::NT_PROPERTY => array(null, 1), // require none to 1 properties
     *     );
     *
     * @return array
     */
    abstract public function getCardinalityMap();

    /**
     * Remove any previous children and add
     * given node via. addChild.
     *
     * @see removeChildrenOfType
     * @see addChild
     *
     * @param AbstractNode $node
     *
     * @return AbstractNode
     */
    public function setChild(AbstractNode $node)
    {
        $this->removeChildrenOfType($node->getNodeType());
        return $this->addChild($node);
    }

    /**
     * Add a child to this node.
     *
     * Exception will be thrown if child node type is not
     * described in the cardinality map, or if the maxiumum
     * permitted number of nodes would be exceeded by adding
     * the given child node.
     *
     * The given node will be returned EXCEPT when the current
     * node is a leaf node, in which case we return the parent.
     *
     * @throws OutOfBoundsException
     *
     * @return AbstractNode
     */
    public function addChild(AbstractNode $node)
    {
        $cardinalityMap = $this->getCardinalityMap();
        $nodeType = $node->getNodeType();

        $validChild = true;
        $end = false;

        // if proposed child node is of an invalid type
        if (!isset($cardinalityMap[$nodeType])) {
            throw new OutOfBoundsException(sprintf(
                'QueryBuilder node "%s" of type "%s" cannot be appended to "%s". '.
                'Must be one type of "%s"',
                $node->getName(),
                $nodeType,
                $this->getName(),
                implode(', ', array_keys($cardinalityMap))
            ));
        }

        $currentCardinality = isset($this->children[$node->getName()]) ?
            count($this->children[$node->getName()]) : 0;

        list($min, $max) = $cardinalityMap[$nodeType];

        // if bounded and cardinality will exceed max
        if (null !== $max && $currentCardinality + 1 > $max) {
            throw new OutOfBoundsException(sprintf(
                'QueryBuilder node "%s" cannot be appended to "%s". '.
                'Number of "%s" nodes cannot exceed "%s"',
                $node->getName(),
                $this->getName(),
                $nodeType,
                $max
            ));
        }

        $this->children[$nodeType][] = $node;

        return $node->getNext();
    }

    /**
     * Return the next object in the fluid interface
     * chain. Leaf nodes always return the parent, deafult
     * behavior is to return /this/ class.
     *
     * @return AbstractNode
     */
    public function getNext()
    {
        return $this;
    }

    /**
     * Return all child nodes.
     *
     * Note that this will returned a flattened version
     * of the classes type => children map.
     *
     * @return AbstractNode[]
     */
    public function getChildren()
    {
        $children = array();
        foreach ($this->children as $type) {
            foreach ($type as $child) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * Return children of specified type.
     *
     * @return AbstractNode[]
     */
    public function getChildrenOfType($type)
    {
        if (!isset($this->children[$type])) {
            return array();
        }

        return $this->children[$type];
    }

    public function removeChildrenOfType($type)
    {
        unset($this->children[$type]);
    }

    /**
     * Return child of node, there must be exactly one child of any type.
     *
     * @return AbstractNode[]
     *
     * @throws OutOfBoundsException if there are more than one or none
     */
    public function getChild()
    {
        $children = $this->getChildren();

        if (!$children) {
            throw new OutOfBoundsException(sprintf(
                'Expected exactly one child, got "%s"',
                count($children)
            ));
        }

        if (count($children) > 1) {
            throw new OutOfBoundsException(sprintf(
                'More than one child node but getChild will only ever return one. "%d" returned.',
                count($children)
            ));
        }

        return current($children);
    }

    /**
     * Return child of specified type.
     *
     * Note: This does not take inheritance into account.
     *
     * @return AbstractNode[]
     *
     * @throws OutOfBoundsException if there are more than one or none
     */
    public function getChildOfType($type)
    {
        $children = $this->getChildrenOfType($type);

        if (!$children) {
            throw new OutOfBoundsException(sprintf(
                'Expected exactly one child of type "%s", got "%s"',
                $type, count($children)
            ));
        }

        if (count($children) > 1) {
            throw new OutOfBoundsException(sprintf(
                'More than one node of type "%s" but getChildOfType will only ever return one.',
                $type
            ));
        }

        return current($children);
    }

    /**
     * Validate the current node.
     *
     * Validation is performed both when the query is being
     * built and when the dev explicitly calls "end()".
     *
     * This method simply checks the minimum boundries are satisfied,
     * the addChild() method already validates maximum boundries and
     * types.
     *
     * @throws OutOfBoundsException
     */
    public function validate()
    {
        $cardinalityMap = $this->getCardinalityMap();
        $typeCount = array();

        foreach (array_keys($cardinalityMap) as $type) {
            $typeCount[$type] = 0;
        }

        foreach ($this->children as $type => $children) {
            $typeCount[$type] += count($children);
        }

        foreach ($typeCount as $type => $count) {
            list($min, $max) = $cardinalityMap[$type];
            if (null !== $min && $count < $min) {
                throw new OutOfBoundsException(sprintf(
                    'QueryBuilder node "%s" must have at least "%s" '.
                    'child nodes of type "%s". "%s" given.',
                    $this->getName(),
                    $min,
                    $type,
                    $count
                ));
            }
        }
    }

    /**
     * Validates this node and returns its parent.
     * This should be used if the developer wants to
     * define the entire Query in a fluid manner.
     *
     * @return AbstractNode
     */
    public function end()
    {
        $this->validate();
        return $this->parent;
    }

    /**
     * Catch any undefined method calls and tell the developer what
     * methods can be called.
     *
     * @throws BadMethodCallException if an unknown method is called.
     */
    public function __call($methodName, $args)
    {
        throw new BadMethodCallException(sprintf(
            'Unknown method "%s" called on node "%s", did you mean one of: "%s"',
            $methodName,
            $this->getName(),
            implode(', ', $this->getFactoryMethods())
        ));
    }

    public function ensureNoArguments($method, $void)
    {
        if ($void) {
            throw new InvalidArgumentException(sprintf(
                'Method "%s" is a factory method and accepts no arguments',
                $method
            ));
        }
    }

    public function getFactoryMethods()
    {
        $refl = new \ReflectionClass($this);

        $fMethods = array();
        foreach ($refl->getMethods() as $rMethod) {
            $comment = $rMethod->getDocComment();
            if ($comment) {
                if (strstr($comment, '@factoryMethod')) {
                    $fMethods[] = $rMethod->name;
                }
            }
        }

        return $fMethods;
    }
}
