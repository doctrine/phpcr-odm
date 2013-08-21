<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandStaticBindVariable extends AbstractLeafNode implements OperandStaticInterface
{
    protected $name;

    public function __construct(AbstractNode $parent, $name)
    {
        $this->name = $name;
        parent::__construct($parent);
    }

    public function getName() 
    {
        return $this->name;
    }
}
