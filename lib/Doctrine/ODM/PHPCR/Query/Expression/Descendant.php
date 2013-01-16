<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;

class Descendant implements Expression
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function visit(ExpressionVisitor $visitor)
    {
        return $visitor->walkDescendantExpression($this);
    }
}
