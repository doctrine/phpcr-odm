<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PHPCR\Util\Console\Command\RegisterNodeTypesCommand;

/**
 * Command to register the phcpr-odm required node types.
 *
 * This command registers the necessary node types to get phpcr odm working
 */
class RegisterSystemNodeTypesCommand extends RegisterNodeTypesCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
        ->setName('doctrine:phpcr:register-system-node-types')
        ->setDescription('Register system node types in the PHPCR repository')
        ->setHelp(<<<EOT
Register system node types in the PHPCR repository.

This command registers the node types necessary for the ODM to work.

If you use --allow-update existing node type definitions will be overwritten
in the repository.
EOT
        );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cnd = <<<CND
<phpcr='http://www.doctrine-project.org/projects/phpcr_odm'>
[phpcr:managed]
  mixin
  - phpcr:class (STRING)
CND
        ;

        $session = $this->getHelper('phpcr')->getSession();

        $this->updateFromCnd($input, $output, $session, $cnd, true);

        $output->write(PHP_EOL.sprintf('Successfully registered system node types.') . PHP_EOL);
    }
}
