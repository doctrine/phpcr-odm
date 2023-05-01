<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\Tools\Console\Helper\DocumentManagerHelper;
use Doctrine\ODM\PHPCR\Tools\Console\MetadataFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to (re)generate the proxy classes used by doctrine.
 *
 * Adapted from the Doctrine ORM command.
 *
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GenerateProxiesCommand extends Command
{
    protected function configure(): void
    {
        $this
        ->setName('doctrine:phpcr:generate-proxies')
        ->setDescription('Generates proxy classes for document classes.')
        ->setDefinition([
            new InputOption(
                'filter',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match entities that should be processed.'
            ),
            new InputArgument(
                'dest-path',
                InputArgument::OPTIONAL,
                'The path to generate your proxy classes. If none is provided, the path from the configuration will be used.'
            ),
        ])
        ->setHelp(
            <<<'EOT'
Generates proxy classes for entity classes.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpcrHelper = $this->getHelper('phpcr');
        \assert($phpcrHelper instanceof DocumentManagerHelper);
        $documentManager = $phpcrHelper->getDocumentManager();
        \assert(null !== $documentManager);

        $metadatas = $documentManager->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

        // Process destination directory
        if (null === ($destPath = $input->getArgument('dest-path'))) {
            $destPath = $documentManager->getConfiguration()->getProxyDir();
        }

        if (!is_dir($destPath)) {
            if (!mkdir($destPath, 0775, true) && !is_dir($destPath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $destPath));
            }
        }

        $destPath = realpath($destPath);

        if (!file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not exist.", $documentManager->getConfiguration()->getProxyDir())
            );
        }
        if (!is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing entity "<info>%s</info>"', $metadata->name).PHP_EOL
                );
            }

            // Generating Proxies
            $documentManager->getProxyFactory()->generateProxyClasses($metadatas, $destPath);

            // Outputting information message
            $output->write(PHP_EOL.sprintf('Proxy classes generated to "<info>%s</INFO>"', $destPath).PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.'.PHP_EOL);
        }

        return 0;
    }
}
