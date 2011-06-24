<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to register system node types.
 *
 * This command registers the necessary node types to get phpcr odm working
 */

class RegisterSystemNodeTypesCommand extends Command
{
   /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:phpcr:register-system-node-types')
        ->setDescription('Register system node types in the PHPCR repository')
        ->setDefinition(array(
            new InputOption('allow-update', '', InputOption::VALUE_NONE, 'Overwrite existig node types'),
        ))
        ->setHelp(<<<EOT
Register system node types in the PHPCR repository.

This command registers the node types necessary for the ODM to work.

If you use --allow-update existing node type definitions will be overwritten
in the repository.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getHelper('dm')->getDocumentManager();

        $cnd = <<<CND
<phpcr='http://www.doctrine-project.org/projects/phpcr_odm'>
[phpcr:managed]
  mixin
  - phpcr:alias (STRING)
  - phpcr:class (STRING)
CND
        ;
        $allowUpdate = $input->getOption('allow-update');

        $session = $dm->getPhpcrSession();
        $ntm = $session->getWorkspace()->getNodeTypeManager();

        try {
            $ntm->registerNodeTypesCnd($cnd, $allowUpdate);
        } catch (\PHPCR\NodeType\NodeTypeExistsException $e) {
            if (!$allowUpdate) {
                $output->write(PHP_EOL.'<error>The node type(s) you tried to register already exist.</error>'.PHP_EOL);
                $output->write(PHP_EOL.'If you want to override the existing definition call this command with the ');
                $output->write('<info>--allow-update</info> option.'.PHP_EOL);
            }
            throw $e;
        }
        $output->write(PHP_EOL.sprintf('Successfully registered system node types.') . PHP_EOL);
    }
}
