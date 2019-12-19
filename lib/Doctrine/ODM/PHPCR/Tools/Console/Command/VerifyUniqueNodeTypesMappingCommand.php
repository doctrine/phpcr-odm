<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\Tools\Helper\UniqueNodeTypeHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verify that any documents which are mapped as having unique
 * node types are truly unique.
 */
class VerifyUniqueNodeTypesMappingCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:phpcr:mapping:verify-unique-node-types')
            ->setDescription('Verify that documents claiming to have unique node types are truly unique')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command checks all mapped PHPCR-ODM documents
and verifies that any claiming to use unique node types are truly unique.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentManager = $this->getHelper('phpcr')->getDocumentManager();
        $uniqueNodeTypeHelper = new UniqueNodeTypeHelper();

        $debugInformation = $uniqueNodeTypeHelper->checkNodeTypeMappings($documentManager);

        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            foreach ($debugInformation as $className => $debug) {
                $output->writeln(sprintf(
                    'The document <info>%s</info> uses %snode type <info>%s</info>',
                    $className,
                    $debug['unique_node_type'] ? '<comment>uniquely mapped</comment> ' : '',
                    $debug['node_type']
                ));
            }
        }

        return 0;
    }
}
