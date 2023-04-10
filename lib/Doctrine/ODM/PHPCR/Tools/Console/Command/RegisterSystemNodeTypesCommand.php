<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\NodeTypeRegistrator;
use PHPCR\Util\Console\Helper\PhpcrHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to register the phcpr-odm required node types.
 *
 * This command registers the necessary node types to get phpcr odm working
 */
class RegisterSystemNodeTypesCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('doctrine:phpcr:register-system-node-types');
        $this->setDescription('Register system node types in the PHPCR repository');
        $this->setHelp(
            <<<'EOT'
Register system node types in the PHPCR repository.

This command registers the node types necessary for the ODM to work.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpcrHelper = $this->getHelper('phpcr');
        \assert($phpcrHelper instanceof PhpcrHelper);
        $session = $phpcrHelper->getSession();
        $registrator = new NodeTypeRegistrator();

        try {
            $registrator->registerNodeTypes($session);
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return 1;
        }

        $output->write(PHP_EOL.sprintf('Successfully registered system node types.').PHP_EOL);

        return 0;
    }
}
