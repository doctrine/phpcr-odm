<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintChildDocument extends AbstractLeafNode
{
    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }
}
