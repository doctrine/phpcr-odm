<?php

namespace Doctrine\ODM\PHPCR\Query;

use Doctrine\Common\Collections\ExpressionBuilder as BaseExpressionBuilder;
use Doctrine\ODM\PHPCR\Query\Expression\DescendantExpression;

class ExpressionBuilder extends BaseExpressionBuilder
{
    public function descendant($path)
    {
        return new DescendantExpression($path);
    }
}
