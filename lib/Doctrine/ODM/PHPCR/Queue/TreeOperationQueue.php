<?php

namespace Doctrine\ODM\PHPCR\Queue;

use Doctrine\ODM\PHPCR\Queue\TreeOperation;
use Doctrine\ODM\PHPCR\Queue\TreeOperationBatch;

class TreeOperationQueue
{
    private $queue = array();

    public function push(TreeOperation $operation)
    {
        $this->queue[] = $operation;
    }

    /**
     * Partition into contiguous sets of operations
     */
    public function getBatches()
    {
        $type = null;
        $batches = array();

        foreach ($this->queue as $operation) {
            if (false === $operation->isValid()) {
                continue;
            }

            if ($operation->getType() !== $type) {
                $batch = new TreeOperationBatch($operation->getType());
                $type = $operation->getType();
                $batches[] = $batch;
            }

            $batch->schedule(
                $operation->getOid(),
                $operation->getArgs()
            );
        }

        return $batches;
    }

    public function clear()
    {
        $this->queue = array();
    }

    public function getSchedule($type)
    {
        $schedule = array();

        foreach ($this->queue as $operation) {
            if (false === $operation->isValid()) {
                continue;
            }

            $schedule[$operation->getOid()] = $operation->getArgs();
        }

        return $schedule;
    }

    public function isQueued($type, $oid)
    {
        foreach ($this->queue as $operation) {
            if (false === $operation->isValid()) {
                continue;
            }

            if ($operation->getType() !== $type) {
                continue;
            }

            if ($oid == $operation->getOid()) {
                return true;
            }
        }

        return false;
    }

    public function unqueue($type, $oid)
    {
        foreach ($this->queue as $operation) {
            if ($operation->getType() !== $type) {
                continue;
            }

            if ($operation->getOid() !== $oid) {
                continue;
            }

            $operation->invalidate();
        }
    }

    public function unregister($oid)
    {
        foreach ($this->queue as $operation) {
            if ($operation->getOid() == $oid) {
                $operation->invalidate();
            }
        }
    }
}
