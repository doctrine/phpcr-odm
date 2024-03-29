<?php

namespace Doctrine\ODM\PHPCR\Tools\Test;

use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;

/**
 * Utility class to help making test assertions on the query
 * builders nodes easier.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
final class QueryBuilderTester
{
    private QueryBuilder $qb;

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
     * @throws BadMethodCallException
     */
    public function getNode(string $path): AbstractNode
    {
        $node = $this->qb;
        $parts = explode('.', $path);
        $currentPath = [];
        $currentNode = $node;

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
                throw new BadMethodCallException(sprintf(
                    "No children at path \"%s\". Node has following paths: \n%s",
                    implode('.', $currentPath),
                    $this->dumpPaths($node)
                ));
            }

            if (!array_key_exists($index, $children)) {
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
     */
    public function dumpPaths(AbstractNode $node = null): string
    {
        $children = [];
        $paths = [];

        if (null === $node) {
            $node = $this->qb;
        }

        foreach ($node->getChildren() as $child) {
            if (!array_key_exists($child->getNodeType(), $children)) {
                $children[$child->getNodeType()] = [];
            }

            $paths[] = $this->getPath($child).' ('.$child->getName().')';

            if (!$child instanceof AbstractLeafNode) {
                $paths[] = $this->dumpPaths($child);
            }
        }

        return implode("\n", $paths);
    }

    public function getPath(AbstractNode $node): string
    {
        $path = [];
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
    public function getAllNodes(AbstractNode $node = null): array
    {
        $nodes = [];
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
