<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Command;

use Doctrine\ODM\PHPCR\Tools\Console\Command\DocumentConvertTranslationCommand;
use Doctrine\ODM\PHPCR\Tools\Helper\TranslationConverter;
use PHPCR\SessionInterface;
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
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslationConverter
     */
    private $converter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private $mockSession;

    public function setUp()
    {
        $this->mockSession = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();
        $mockHelper = $this->getMockBuilder('PHPCR\Util\Console\Helper\PhpcrHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $mockHelper
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->mockSession))
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
        $this->converter
            ->expects($this->any())
            ->method('getLastNotices')
            ->will($this->returnValue(array()))
        ;

        $this->mockSession
            ->expects($this->once())
            ->method('save')
        ;

        $this->commandTester->execute(array(
            'classname' => 'Document\MyClass',
            '--locales' => array('en'),
            '--force' => true,
        ));

        $this->assertEquals('.'.PHP_EOL.'done'.PHP_EOL, $this->commandTester->getDisplay());
    }
}
