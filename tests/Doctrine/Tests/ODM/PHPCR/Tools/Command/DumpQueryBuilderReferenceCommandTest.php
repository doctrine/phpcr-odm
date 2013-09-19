<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Command;

use Doctrine\ODM\PHPCR\Tools\Console\Command\DumpQueryBuilderReferenceCommand;
use Symfony\Component\Console\Tester\CommandTester;

class QueryBuilderReferenceCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->command = new DumpQueryBuilderReferenceCommand();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommand()
    {
        $this->commandTester->execute(array());
        $res = $this->commandTester->getDisplay();
        $this->assertContains('Query Builder Reference', $res);
    }
}
