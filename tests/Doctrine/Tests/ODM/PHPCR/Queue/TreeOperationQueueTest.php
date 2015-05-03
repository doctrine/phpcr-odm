<?php

namespace Doctrine\Tests\ODM\PHPCR\Queue;

use Doctrine\ODM\PHPCR\Queue\TreeOperationQueue;
use Doctrine\ODM\PHPCR\Queue\TreeOperation;

class TreeOperationQueueTest extends \PHPUnit_Framework_Testcase
{
    private $queue;

    public function setUp()
    {
        $this->queue = new TreeOperationQueue();
    }

    public function provideGetBatches()
    {
        return array(
            array(
                array(
                    TreeOperation::OP_MOVE,
                    TreeOperation::OP_INSERT,
                    TreeOperation::OP_REMOVE,
                ),
                3
            ),
            array(
                array(
                    TreeOperation::OP_REMOVE,
                ),
                1
            ),
            array(
                array(
                    TreeOperation::OP_REMOVE,
                    TreeOperation::OP_REMOVE,
                    TreeOperation::OP_INSERT,
                    TreeOperation::OP_MOVE,
                    TreeOperation::OP_REMOVE,
                ),
                4
            )
        );
    }

    /**
     * @dataProvider provideGetBatches
     */
    public function testGetBatches($operations, $expectedNbBatches)
    {
        foreach ($operations as $operation) {
            $this->queue->push(new TreeOperation($operation, '1234', 'arg'));
        }

        $batches = $this->queue->getBatches();

        $this->assertCount($expectedNbBatches, $batches);
    }

    public function provideSchedule()
    {
        return array(
            array(
                array(
                    array(
                        TreeOperation::OP_MOVE,
                        1,
                        'arg1',
                        true
                    ),
                    array(
                        TreeOperation::OP_MOVE,
                        2,
                        'arg2',
                        false
                    ),
                    array(
                        TreeOperation::OP_REMOVE,
                        6,
                        'arg3',
                        true
                    ),
                    array(
                        TreeOperation::OP_MOVE,
                        3,
                        'arg3',
                        true
                    ),
                ),
                TreeOperation::OP_MOVE,
                array(
                    '1' => 'arg1',
                    '3' => 'arg3',
                ),
            )
        );
    }

    /**
     * @dataProvider provideSchedule
     */
    public function testSchedule($operations, $targetType, $expectedSchedule)
    {
        foreach ($operations as $operation) {
            $treeOperation = new TreeOperation($operation[0], $operation[1], $operation[2]);
            if (!$operation[3]) {
                $treeOperation->invalidate();
            }

            $this->queue->push($treeOperation);
        }

        $this->assertEquals($expectedSchedule, $this->queue->getSchedule($targetType));
    }

    public function testIsQueued()
    {
        $this->queue->push(new TreeOperation(TreeOperation::OP_MOVE, '1234', 'arg'));
        $this->assertTrue($this->queue->isQueued(TreeOperation::OP_MOVE, '1234'));
        $this->assertFalse($this->queue->isQueued(TreeOperation::OP_MOVE, '4321'));
        $this->assertFalse($this->queue->isQueued(TreeOperation::OP_INSERT, '1234'));
    }

    public function testUnqueue()
    {
        $this->queue->push(new TreeOperation(TreeOperation::OP_MOVE, '1234', 'arg'));
        $this->queue->unqueue(TreeOperation::OP_MOVE, '1234');
        $this->assertFalse($this->queue->isQueued(TreeOperation::OP_MOVE, '1234'));
    }

    public function testUnregister()
    {
        $this->queue->push(new TreeOperation(TreeOperation::OP_MOVE, '1234', 'arg'));
        $this->queue->unregister('1234');
        $this->assertFalse($this->queue->isQueued(TreeOperation::OP_MOVE, '1234'));
    }
}
