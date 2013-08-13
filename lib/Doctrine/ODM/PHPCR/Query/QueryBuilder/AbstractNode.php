<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

/**
 * All QueryBuilder nodes extend this class.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class AbstractNode
{
    protected $children = array();
    protected $parent;

    public function __construct(AbstractNode $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return the last part of the this classes FQN (i.e. the basename)
     *
     * @return string
     */
    public function getName()
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
     *         'JoinCondition' => array(1, 1), // require exactly 1 join condition
     *         'Source' => array(2, 2), // exactly 2 sources
     *     );
     *
     * or:
     *     array(
     *         'Column' => array(1, null), // require one to many Columns
     *     );
     *
     * or:
     *     array(
     *         'FooBar' => array(null, 1), // require none to 1 FooBars
     *     );
     *
     * or for a leaf node:
     *     array(
     *     );
     *
     * @return array
     */
    abstract public function getCardinalityMap();

    /**
     * Add a child to this node.
     *
     * Exception will be thrown if given child is of
     * an unmapped type or if adding given child would
     * exceed the upper bound.
     *
     * The given node will be returned EXCEPT when the current 
     * node is a leaf node, in which case we return the parent.
     *
     * @throws \OutOfBoundsException
     * @return AbstractNode
     */
    public function addChild(AbstractNode $node)
    {
        $cardinalityMap = $this->getCardinalityMap();

        $validChild = true;
        $end = false;

        // if proposed child node is of an invalid type
        if (!isset($cardinalityMap[$node->getName()])) {
            throw new \OutOfBoundsException(sprintf(
                'QueryBuilder node "%s" cannot be appended to "%s". '.
                'Must be one of "%s"',
                $node->getName(),
                $this->getName(),
                implode(', ', array_keys($cardinalityMap))
            ));
        }

        $currentCardinality = isset($this->children[$node->getName()]) ? 
            count($this->children[$node->getName()]) : 0;

        list($min, $max) = $cardinalityMap[$node->getName()];

        // if bounded and cardinality will exceed max
        if (null !== $max && $currentCardinality + 1 > $max) {
            throw new \OutOfBoundsException(sprintf(
                'QueryBuilder node "%s" cannot be appended to "%s". '.
                'Number of "%s" nodes cannot exceed "%s"',
                $node->getName(),
                $this->getName(),
                $node->getName(),
                $max
            ));
        }

        $this->children[$node->getName()][] = $node;

        // return the parent node if this is a leaf node
        if (count($node->getCardinalityMap()) === 0) {
            return $node->getParent();
        }

        return $node;
    }

    /**
     * Return all child nodes.
     *
     * Note that this will returned a flattened version
     * of the classes type => children map.
     *
     * @return array AbstractNode[]
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
     * Validate the current node.
     *
     * Validation is performed both when the query is being
     * built and when the dev explicitly calls "end()".
     *
     * This method simply checks the minimum boundries are satisfied,
     * the addChild() method already validates maximum boundries and
     * types.
     *
     * @throws \OutOfBoundsException
     * @return void
     */
    public function validate()
    {
        $cardinalityMap = $this->getCardinalityMap();
        $typeCount = array();

        // initialize DS
        foreach (array_keys($cardinalityMap) as $type) {
            $typeCount[$type] = 0;
        }

        foreach ($this->children as $type => $children) {
            $typeCount[$type] += count($children);
        }

        foreach ($typeCount as $type => $count) {
            list($min, $max) = $cardinalityMap[$type];
            if (null !== $min && $count < $min) {
                throw new \OutOfBoundsException(sprintf(
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
}
