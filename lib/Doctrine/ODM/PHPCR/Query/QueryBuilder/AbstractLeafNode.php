<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

/**
 * Special class for leaf nodes. Leaf (have no children)
 * and always return the parent rather than themselves.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class AbstractLeafNode extends AbstractNode
{
    public function getNext()
    {
        return $this->getParent();
    }

    public function getChildren()
    {
        throw new \RuntimeException(sprintf(
            'Cannot call getChildren on leaf node "%s"',
            $this->getName()
        ));
    }

    public function addChild()
    {
        throw new \RuntimeException(sprintf(
            'Cannot call addChild to leaf node "%s"',
            $this->getName()
        ));
    }

    public function getCardinalityMap()
    {
        // no children , no cardinality map...
        return array();
    }
}
