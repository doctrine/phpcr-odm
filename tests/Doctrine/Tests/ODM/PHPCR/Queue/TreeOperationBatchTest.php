<?php

namespace Doctrine\Tests\ODM\PHPCR\Queue;

use Doctrine\ODM\PHPCR\Queue\TreeOperationBatch;
use Doctrine\ODM\PHPCR\Queue\TreeOperation;

class TreeOperationBatchTest extends \PHPUnit_Framework_Testcase
{
    private $batch;

    public function setUp()
    {
        $this->batch = new TreeOperationBatch(TreeOperation::OP_MOVE);
    }

    public function testGetters()
    {
        $this->assertEquals(TreeOperation::OP_MOVE, $this->batch->getType());
    }

    public function testSchedule()
    {
        $this->batch->schedule('1234', 'arg');
        $this->batch->schedule('4321', 'arg');

        $this->assertEquals(array(
            '1234' => 'arg',
            '4321' => 'arg',
        ), $this->batch->getSchedule());
    }
}

