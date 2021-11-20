<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Command;

use Doctrine\ODM\PHPCR\Tools\Console\Command\DumpQueryBuilderReferenceCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DumpQueryBuilderReferenceCommandTest extends TestCase
{
    /**
     * @var DumpQueryBuilderReferenceCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function setUp(): void
    {
        $this->command = new DumpQueryBuilderReferenceCommand();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommand()
    {
        $this->commandTester->execute([]);
        $res = $this->commandTester->getDisplay();
        $this->assertStringContainsStringIgnoringCase('Query Builder Reference', $res);
    }
}
