<?php

namespace Doctrine\Tests\ODM\PHPCR\Queue;

use Doctrine\ODM\PHPCR\Queue\TreeOperation;

class TreeOperationTest extends \PHPUnit_Framework_Testcase
{
    private $treeOperation;

    public function setUp()
    {
        $this->treeOperation = new TreeOperation(TreeOperation::OP_MOVE, '1234', 'arg');
    }

    public function testGetters()
    {
        $this->assertEquals('1234', $this->treeOperation->getOid());
        $this->assertEquals('arg', $this->treeOperation->getArgs());
        $this->assertEquals(TreeOperation::OP_MOVE, $this->treeOperation->getType());
    }

    public function testInvalidate()
    {
        $this->assertTrue($this->treeOperation->isValid());
        $this->treeOperation->invalidate();
        $this->assertFalse($this->treeOperation->isValid());
    }
}
