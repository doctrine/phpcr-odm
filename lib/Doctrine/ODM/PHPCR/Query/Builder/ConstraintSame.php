<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint which evaluates to true if the aliased document is
 * reachable by the specified path.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintSame extends AbstractLeafNode
{
    private string $alias;
    private string $path;

    public function __construct(AbstractNode $parent, string $alias, string $path)
    {
        $this->alias = $alias;
        $this->path = $path;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_CONSTRAINT;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
