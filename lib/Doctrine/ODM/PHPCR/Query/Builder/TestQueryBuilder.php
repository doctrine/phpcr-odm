<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\PHPCRBadMethodCallException;

/**
 * Special instance of QueryBuilder providing method(s) which
 * are help when using the builder in a testing scenario.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class TestQueryBuilder extends QueryBuilder
{
    /**
     * Retrieve node by a given path.
     *
     * <code>
     * $qb->getNodeByPath('where.constraint[0].operand[2]');
     * </code>
     *
     * The path components relate to the NT_& constants in the
     * AbstractNode
     *
     * @param string $path
     *
     * @return AbstractNode
     */
    public function getNodeByPath($path)
    {
        $components = explode('.', $path);

        $iteratedComponents = array();
        $currentNode = $this;
        foreach ($components as $component) {
            if (preg_match('&^([a-z_]*)\[([0-9]+)]$&', $component, $matches)) {
                $element = $matches[1];
                $index = $matches[2];
                $children = $currentNode->getChildrenOfType($element);

                if (!isset($children[$index])) {
                    throw new \InvalidArgumentException(sprintf('No child of node type "%s" at index "%s" at path "%s"',
                        $element, $index, implode(' > ', $iteratedComponents)
                    ));
                }

                $currentNode = $children[$index];
            } else {
                $children = $currentNode->getChildrenOfType($component);
                $currentNode = current($children);

                if (!$currentNode) {
                    throw new \InvalidArgumentException(sprintf('No children at path "%s" of type "%s"',
                        implode(' > ', $iteratedComponents), $component
                    ));
                }
            }

            $iteratedComponents[] = $component;
        }

        return $currentNode;
    }
}
