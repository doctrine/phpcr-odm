<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class SourceJoinConditionEqui extends AbstractLeafNode
{
    protected $property1;
    protected $alias1;
    protected $property2;
    protected $alias2;

    public function __construct($parent, $field1, $field2)
    {
        list($alias1, $property1) = $this->explodeField($field1);
        list($alias2, $property2) = $this->explodeField($field2);
        parent::__construct($parent);
        $this->property1 = $property1;
        $this->alias1 = $alias1;
        $this->property2 = $property2;
        $this->alias2 = $alias2;
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getProperty1() 
    {
        return $this->property1;
    }

    public function getAlias1() 
    {
        return $this->alias1;
    }

    public function getProperty2() 
    {
        return $this->property2;
    }

    public function getAlias2() 
    {
        return $this->alias2;
    }
}

