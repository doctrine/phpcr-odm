<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class Field extends AbstractLeafNode
{
    private string $field;
    private string $alias;

    public function __construct(AbstractNode $parent, string $field)
    {
        [$alias, $field] = $this->explodeField($field);
        $this->field = $field;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getNodeType(): string
    {
        return self::NT_PROPERTY;
    }
}
