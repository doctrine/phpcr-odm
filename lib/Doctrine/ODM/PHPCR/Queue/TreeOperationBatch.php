<?php

namespace Doctrine\ODM\PHPCR\Queue;

class TreeOperationBatch
{
    private $type;
    private $schedule = array();

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function getSchedule() 
    {
        return $this->schedule;
    }

    public function schedule($oid, $args)
    {
        $this->schedule[$oid] = $args;
    }

    public function getType() 
    {
        return $this->type;
    }
}
