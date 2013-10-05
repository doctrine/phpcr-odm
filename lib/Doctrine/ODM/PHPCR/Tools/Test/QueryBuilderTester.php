<?php

namespace Doctrine\ODM\PHPCR\Tools\Test;

use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;

/**
 * Utility class to help making test assertions on the query
 * builders nodes easier.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class QueryBuilderTester
{
    protected $qb;

    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * Return the query builder node found at the given path.
     *
     * Paths are made from a series of $nodeType[$index], e.g.
     *
     *     $qb->where()->andX()->eq()->field('f.foo')->literal('Foo');
     *
     * The following path will retrieve the "literal" node:
     *
     *     where.constraint.constraint[1].operand_static
     *
     * @param string $path
     *
     * @return AbstractNode
     *
     * @throws BadMethodCallException
     */
    public function getNode($path)
    {
        $node = $this->qb;
        $parts = explode('.', $path);
        $currentPath = array();
        $currentNode = $node;

        $index = 0;
        foreach ($parts as $part) {
            if (preg_match('&^([a-z]+)\[([0-9]+)\]$&', $part, $matches)) {
                $nodeType = $matches[1];
                $index = $matches[2];
            } else {
                $index = 0;
                $nodeType = $part;
            }

            $currentPath[] = $nodeType.'['.$index.']';

            $children = $currentNode->getChildrenOfType($nodeType);

            if (!$children) {
                throw new BadMethodCallException(sprintf("No children at path \"%s\". Node has following paths: \n%s",
                    implode('.', $currentPath),
                    $this->dumpPaths($node)
                ));
            }

            if (!isset($children[$index])) {
                throw new BadMethodCallException(sprintf(
                    "No node at path \"%s\". Node has following paths: \n%s",
                    implode('.', $currentPath),
                    $this->dumpPaths($node)
                ));
            }

            $currentNode = $children[$index];
        }

        return $currentNode;
    }

    /**
     * Dump the path of each node in the query builder tree.
     *
     * Note that paths here do not include indexes. They need to be
     * inferred mentally.
     *
     * @param AbstractNode $node
     *
     * @return string
     */
    public function dumpPaths(AbstractNode $node = null)
    {
        $children = array();
        $paths = array();

        if (null === $node) {
            $node = $this->qb;
        }

        foreach ($node->getChildren() as $child) {
            if (!isset($children[$child->getNodeType()])) {
                $children[$child->getNodeType()] = array();
            }

            $paths[] = $this->getPath($child).' ('.$child->getName().')';

            if (!$child instanceof AbstractLeafNode) {
                $paths[] = $this->dumpPaths($child);
            }
        }

        return implode("\n", $paths);
    }

    /**
     * Get path of given node.
     *
     * @param AbstractNode
     *
     * @return string
     */
    public function getPath(AbstractNode $node)
    {
        $path = array();
        $currentNode = $node;

        do {
            $path[] = $currentNode->getNodeType();
        } while ($currentNode = $currentNode->getParent());

        $path = array_reverse($path);
        array_shift($path);

        return implode('.', $path);
    }

    /**
     * Return all the nodes in the query builder.
     *
     * @return AbstractNode[]
     */
    public function getAllNodes(AbstractNode $node = null)
    {
        $nodes = array();
        if (!$node) {
            $node = $this->qb;
        }

        foreach ($node->getChildren() as $child) {
                $nodes[] = $child;
            if (!$child instanceof AbstractLeafNode) {
                $nodes = array_merge($nodes, $this->getAllNodes($child));
            }
        }

        return $nodes;
    }
}
