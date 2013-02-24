<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;

/**
 * This class purpose is to provide  provide a place
 * for the LIKE constant. Everything  else is handled
 * in Doctrine\Common\Collections\Expr\Comparison
 */
class Comparison extends BaseComparison
{
    const LIKE = 'like';
}
