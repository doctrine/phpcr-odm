<?php

namespace Doctrine\ODM\PHPCR\Queue;

class TreeOperation
{
    const OP_MOVE = 'move';
    const OP_REMOVE = 'remove';
    const OP_INSERT = 'insert';

    private $oid;
    private $type;
    private $args;
    private $valid = true;

    public function __construct($type, $oid, $args)
    {
        $this->oid = $oid;
        $this->type = $type;
        $this->args = $args;
    }

    public function getOid() 
    {
        return $this->oid;
    }

    public function getType() 
    {
        return $this->type;
    }

    public function getArgs() 
    {
        return $this->args;
    }

    public function invalidate()
    {
        $this->valid = false;
    }

    public function isValid()
    {
        return $this->valid;
    }
}
