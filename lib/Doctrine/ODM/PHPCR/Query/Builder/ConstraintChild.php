<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint evaluates to true if aliased node is a child of
 * the given parent path for the given alias.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintChild extends AbstractLeafNode
{
    private string $alias;
    private string $parentPath;

    public function __construct(AbstractNode $parent, string $alias, string $parentPath)
    {
        $this->alias = $alias;
        $this->parentPath = $parentPath;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_CONSTRAINT;
    }

    public function getParentPath(): string
    {
        return $this->parentPath;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }
}
