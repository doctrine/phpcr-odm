<?php

namespace Doctrine\ODM\PHPCR\Query;

class NoResultException extends QueryException
{
    public function __construct()
    {
        parent::__construct('Expected result from query, didn\'t get one.');
    }
}
