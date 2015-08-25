<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Command;

use Doctrine\ODM\PHPCR\Tools\Console\Command\DumpQueryBuilderReferenceCommand;
use Symfony\Component\Console\Tester\CommandTester;

class DumpQueryBuilderReferenceCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DumpQueryBuilderReferenceCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function setUp()
    {
        $this->command = new DumpQueryBuilderReferenceCommand();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommand()
    {
        if (0 == strpos(phpversion(), '5.3')) {
            $this->markTestSkipped('Dump reference command not compatible with PHP 5.3');
        }

        $this->commandTester->execute(array());
        $res = $this->commandTester->getDisplay();
        $this->assertContains('Query Builder Reference', $res);
    }
}
