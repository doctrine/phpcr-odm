<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Queue;

use Doctrine\ODM\PHPCR\Queue\TreeOperation;
use Doctrine\ODM\PHPCR\Queue\TreeOperationBatch;

/**
 * The tree operation queue
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class TreeOperationQueue
{
    private $queue = array();

    /**
     * Push a new operation onto the queue
     *
     * @param TreeOperation $operation
     */
    public function push(TreeOperation $operation)
    {
        $this->queue[] = $operation;
    }

    /**
     * Partition into contiguous sets of operations
     *
     * @return TreeOperationBatch[]
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

    /**
     * Clear the queue
     */
    public function clear()
    {
        $this->queue = array();
    }

    /**
     * Return the entire schedule for the given operation type
     *
     * @param string $type
     *
     * @return array
     */
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

    /**
     * Return true if the spl object id is scheduled for the given type
     *
     * @param string $type
     * @param string $oid
     *
     * @return boolean
     */
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

    /**
     * Remove all operations with the given spl object ID and type
     *
     * @param string $type
     * @param string $oid
     */
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

    /**
     * Remove all references to the given spl object ID
     *
     * @param $oid string
     */
    public function unregister($oid)
    {
        foreach ($this->queue as $operation) {
            if ($operation->getOid() == $oid) {
                $operation->invalidate();
            }
        }
    }
}
