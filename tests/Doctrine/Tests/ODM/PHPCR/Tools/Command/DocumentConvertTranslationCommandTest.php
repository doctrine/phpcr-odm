<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Command;

use Doctrine\ODM\PHPCR\Tools\Console\Command\DocumentConvertTranslationCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

class DocumentConvertTranslationCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentConvertTranslationCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $converter;

    public function setUp()
    {
        $mockSession = $this->getMock('PHPCR\SessionInterface');
        $mockHelper = $this->getMock(
            'Symfony\Component\Console\Helper\HelperInterface',
            array('getSession', 'setHelperSet', 'getHelperSet', 'getName')
        );
        $mockHelper
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($mockSession))
        ;
        $this->converter = $this->getMockBuilder('Doctrine\ODM\PHPCR\Tools\Helper\TranslationConverter')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->command = new DocumentConvertTranslationCommand(null, $this->converter);
        $this->command->setHelperSet(new HelperSet(
            array('phpcr' => $mockHelper)
        ));
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommand()
    {
        $this->converter
            ->expects($this->once())
            ->method('convert')
            ->with('Document\MyClass', array('en'), array(), 'none')
            ->will($this->returnValue(false))
        ;
        $this->commandTester->execute(array(
            'classname' => 'Document\MyClass',
            '--locales' => array('en'),
            '--force' => true,
        ));
        $this->assertEquals("done".PHP_EOL, $this->commandTester->getDisplay());
    }
}
