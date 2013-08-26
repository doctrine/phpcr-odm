<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintDescendantDocument extends AbstractLeafNode
{
    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }
}
