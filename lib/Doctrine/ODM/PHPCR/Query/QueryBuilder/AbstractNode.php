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
     * @return array
     */
    abstract public function getCardinalityMap();

    protected function isValidType($node)
    {
        return $this->getBaseType($node->getName()) !== null;
    }

    protected function getBaseType($nodeName)
    {
        foreach (array_keys($this->getCardinalityMap()) as $type) {
            $validFqn = __NAMESPACE__.'\\'.$type;
            $nodeFqn = __NAMESPACE__.'\\'.$nodeName;

            // silly hack for unit tests...
            if ($nodeName == $type) {
                return $type;
            }

            if (!class_exists($nodeFqn)) {
                return null;
            }

            $refl = new \ReflectionClass($nodeFqn);

            // support polymorphism
            if ($refl->isSubclassOf($validFqn)) {
                return $type;
            }
        }

        return null;
    }

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
        if (!$this->isValidType($node)) {
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

        list($min, $max) = $cardinalityMap[$this->getBaseType($node->getName())];

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

        return $node->getNext();
    }

    /**
     * Return the next object in the fluid interface
     * chain.
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
     * Return children of specified type.
     *
     * @return array AbstractNode[]
     */
    public function getChildrenOfType($type) 
    {
        if (isset($this->children[$type])) {
            return $this->children[$type];
        }

        return array();
    }

    /**
     * Return child of specified type.
     * 
     * @throws \OutOfBoundsException if there are more than one or none
     * @return array AbstractNode[]
     */
    public function getChildOfType($type) 
    {
        if (isset($this->children[$type])) {
            $node = $this->children[$type];
            if (count($node) > 1) {
                throw new \OutOfBoundsException(sprintf(
                    'More than one node of type "%s" but getChildOfType will only ever return one.',
                    $type
                ));
            }

            return current($node);
        }

        throw new \OutOfBoundsException(sprintf(
            'getChildOfType called, but no nodes of type "%s" exist.',
            $type
        ));
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

        foreach (array_keys($cardinalityMap) as $type) {
            $typeCount[$type] = 0;
        }

        foreach ($this->children as $type => $children) {
            $type = $this->getBaseType($type);
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

    public function __call($methodName, $args)
    {
        throw new \RuntimeException(sprintf(
            'Unknown method "%s", did you mean one of: "%s"',
            $methodName,
            implode(',', get_class_methods($this))
        ));
    }
}
