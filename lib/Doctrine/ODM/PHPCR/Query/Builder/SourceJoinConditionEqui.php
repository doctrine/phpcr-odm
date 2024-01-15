<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class SourceJoinConditionEqui extends AbstractLeafNode
{
    private string $property1;

    private string $alias1;

    private string $property2;

    private string $alias2;

    public function __construct(AbstractNode $parent, string $field1, string $field2)
    {
        [$alias1, $property1] = $this->explodeField($field1);
        [$alias2, $property2] = $this->explodeField($field2);
        parent::__construct($parent);
        $this->property1 = $property1;
        $this->alias1 = $alias1;
        $this->property2 = $property2;
        $this->alias2 = $alias2;
    }

    public function getNodeType(): string
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getProperty1(): string
    {
        return $this->property1;
    }

    public function getAlias1(): string
    {
        return $this->alias1;
    }

    public function getProperty2(): string
    {
        return $this->property2;
    }

    public function getAlias2(): string
    {
        return $this->alias2;
    }
}
