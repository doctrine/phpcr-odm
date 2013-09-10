<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class Where extends ConstraintFactory
{
    public function getNodeType()
    {
        return self::NT_WHERE;
    }
}
