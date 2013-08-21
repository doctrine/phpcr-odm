<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandStaticBindVariable extends AbstractLeafNode implements OperandStaticInterface
{
    protected $variableName;

    public function __construct(AbstractNode $parent, $variableName)
    {
        $this->variableName = $variableName;
        parent::__construct($parent);
    }

    public function getVariableName() 
    {
        return $this->variableName;
    }
}
