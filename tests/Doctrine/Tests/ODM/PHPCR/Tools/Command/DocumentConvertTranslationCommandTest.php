<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Command;

use Doctrine\ODM\PHPCR\Tools\Console\Command\DocumentConvertTranslationCommand;
use Doctrine\ODM\PHPCR\Tools\Helper\TranslationConverter;
use PHPCR\SessionInterface;
use PHPCR\Util\Console\Helper\PhpcrHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

class DocumentConvertTranslationCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var TranslationConverter|MockObject
     */
    private $converter;

    /**
     * @var SessionInterface|MockObject
     */
    private $mockSession;

    public function setUp(): void
    {
        $this->mockSession = $this->createMock(SessionInterface::class);
        $mockHelper = $this->createMock(PhpcrHelper::class);
        $mockHelper
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($this->mockSession);
        $this->converter = $this->createMock(TranslationConverter::class);
        $command = new DocumentConvertTranslationCommand(null, $this->converter);
        $command->setHelperSet(new HelperSet(
            ['phpcr' => $mockHelper]
        ));
        $this->commandTester = new CommandTester($command);
    }

    public function testCommand(): void
    {
        $this->converter
            ->expects($this->once())
            ->method('convert')
            ->with('Document\MyClass', ['en'], [], 'none')
            ->willReturn(false);
        $this->converter
            ->expects($this->any())
            ->method('getLastNotices')
            ->willReturn([]);

        $this->mockSession
            ->expects($this->once())
            ->method('save');

        $this->commandTester->execute([
            'classname' => 'Document\MyClass',
            '--locales' => ['en'],
            '--force' => true,
        ]);

        $this->assertEquals('.'.PHP_EOL.'done'.PHP_EOL, $this->commandTester->getDisplay());
    }
}
