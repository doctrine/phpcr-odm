<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

use Doctrine\Common\Collections\Expr\Comparison;

final class NodeLocalName
{
    private Comparison $comparison;

    public function __construct($operator, $value)
    {
        $this->comparison = new Comparison(null, $operator, $value);
    }

    public function getComparison(): Comparison
    {
        return $this->comparison;
    }
}
