<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;

class Descendant implements Expression
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function visit(ExpressionVisitor $visitor)
    {
    }
}
