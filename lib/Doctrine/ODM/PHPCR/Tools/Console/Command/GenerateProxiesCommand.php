<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\DocumentManager;
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
 * @see    www.doctrine-project.org
 * @since   PHPCR-ODM 1.1
 *
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GenerateProxiesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $documentManager DocumentManager */
        $documentManager = $this->getHelper('phpcr')->getDocumentManager();

        $metadatas = $documentManager->getMetadataFactory()->getAllMetadata();
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

        // Process destination directory
        if (null === ($destPath = $input->getArgument('dest-path'))) {
            $destPath = $documentManager->getConfiguration()->getProxyDir();
        }

        if (!is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);

        if (!file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not exist.", $documentManager->getConfiguration()->getProxyDir())
            );
        } elseif (!is_writable($destPath)) {
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
