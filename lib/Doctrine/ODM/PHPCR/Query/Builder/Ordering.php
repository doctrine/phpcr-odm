<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class Ordering extends OperandDynamicFactory
{
    private string $order;

    public function __construct(AbstractNode $parent, string $order)
    {
        $this->order = $order;
        parent::__construct($parent);
    }

    public function getCardinalityMap(): array
    {
        return [
            self::NT_OPERAND_DYNAMIC => [1, 1],
        ];
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function getNodeType(): string
    {
        return self::NT_ORDERING;
    }
}
