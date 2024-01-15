<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Operand evaluates to the value of the given field of the
 * aliased document.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandDynamicField extends AbstractLeafNode
{
    private string $alias;

    private string $field;

    public function __construct(AbstractNode $parent, string $field)
    {
        [$alias, $field] = $this->explodeField($field);
        $this->alias = $alias;
        $this->field = $field;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_OPERAND_DYNAMIC;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
