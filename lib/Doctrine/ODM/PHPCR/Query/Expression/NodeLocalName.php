<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

class NodeLocalName extends Comparison
{
    public function __construct($operator, $value)
    {
        parent::__construct(null, $operator, $value);
    }
}
