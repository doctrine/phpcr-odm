<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Where extends ConstraintFactory
{
    public function getNodeType()
    {
        return self::NT_WHERE;
    }
}
