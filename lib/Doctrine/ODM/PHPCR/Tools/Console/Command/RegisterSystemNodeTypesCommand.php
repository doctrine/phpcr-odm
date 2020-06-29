<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\NodeTypeRegistrator;
use PHPCR\SessionInterface;
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
    /**
     * @see Command
     */
    protected function configure()
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SessionInterface $session */
        $session = $this->getHelper('phpcr')->getSession();
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
