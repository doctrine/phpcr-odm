<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

/**
 * This class purpose is to provide provide a place
 * for the LIKE constant. Everything else is handled
 * in Doctrine\Common\Collections\Expr\Comparison.
 */
final class Comparison
{
    public const LIKE = 'like';
}
