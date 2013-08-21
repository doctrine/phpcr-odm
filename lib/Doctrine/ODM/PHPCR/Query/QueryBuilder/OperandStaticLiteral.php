<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandStaticLiteral extends AbstractLeafNode implements OperandStaticInterface
{
    protected $value;

    public function __construct(AbstractNode $parent, $value)
    {
        $this->value = $value;
        parent::__construct($parent);
    }

    public function getValue() 
    {
        return $this->value;
    }
}
