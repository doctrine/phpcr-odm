<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint which evaluates to true when the aliased document
 * path is a descendant of the specified ancestor path.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintDescendant extends AbstractLeafNode
{
    private string $alias;
    private string $ancestorPath;

    public function __construct(AbstractNode $parent, string $alias, string $ancestorPath)
    {
        $this->alias = $alias;
        $this->ancestorPath = $ancestorPath;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_CONSTRAINT;
    }

    public function getAncestorPath(): string
    {
        return $this->ancestorPath;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }
}
