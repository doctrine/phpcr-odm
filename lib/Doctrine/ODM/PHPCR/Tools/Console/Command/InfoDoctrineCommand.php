<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\ODM\PHPCR\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show information about mapped documents.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class InfoDoctrineCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:phpcr:mapping:info')
            ->setDescription('Shows basic information about all mapped documents')
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> shows basic information about which
documents exist and possibly if their mapping information contains errors or
not.

<info>php app/console doctrine:phpcr:mapping:info</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpcrHelper = $this->getHelper('phpcr');
        \assert($phpcrHelper instanceof DocumentManagerHelper);
        $documentManager = $phpcrHelper->getDocumentManager();
        \assert(null !== $documentManager);

        $documentClassNames = $documentManager->getConfiguration()
            ->getMetadataDriverImpl()
            ->getAllClassNames();

        if (!$documentClassNames) {
            throw new \LogicException(
                'You do not have any mapped Doctrine PHPCR ODM documents according to the current configuration. '.
                'If you have entities or mapping files you should check your mapping configuration for errors.'
            );
        }

        $output->writeln(sprintf('Found <info>%d</info> documents mapped in document manager:', count($documentClassNames)));

        $failure = false;

        foreach ($documentClassNames as $documentClassName) {
            try {
                $documentManager->getClassMetadata($documentClassName);
                $output->writeln(sprintf('<info>[OK]</info>   %s', $documentClassName));
            } catch (MappingException $e) {
                $output->writeln('<error>[FAIL]</error> '.$documentClassName);
                $output->writeln(sprintf('<comment>%s</comment>', $e->getMessage()));

                if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                    $application = $this->getApplication();
                    if (method_exists($application, 'renderThrowable')) {
                        $application->renderThrowable($e, $output);
                    } else {
                        // support legacy symfony console
                        $application->renderException($e, $output); /* @phpstan-ignore-line */
                    }
                }

                $output->writeln('');

                $failure = true;
            }
        }

        return $failure ? 1 : 0;
    }
}
