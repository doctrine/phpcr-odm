<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

/**
 * Command to load and register a node type defined in a CND file.
 *
 * See the link below for the cnd definition.
 * @link http://jackrabbit.apache.org/node-type-notation.html
 */

class RegisterNodeTypesCommand extends Console\Command\Command
{
   /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:phpcr:register-node-types')
        ->setDescription('Register node types in the PHPCR repository')
        ->setDefinition(array(
            new InputArgument(
                'cnd-file', InputArgument::REQUIRED, 'The file that contains the node type definitions'
            ),
        ))
        ->setHelp(<<<EOT
Register node types in the PHPCR repository.

This command allows to register node types in the repository that are defined
in a CND (Compact Node Definition) file.

Custom node types can be used to define the structure of content repository
nodes, like allowed properties and child nodes.
EOT
        );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $dm = $this->getHelper('dm')->getDocumentManager();

        $cnd_file = realpath($input->getArgument('cnd-file'));

        if (!file_exists($cnd_file)) {
            throw new \InvalidArgumentException(
                sprintf("Node type definition file '<info>%s</info>' does not exist.", $cnd_file)
            );
        } else if (!is_readable($cnd_file)) {
            throw new \InvalidArgumentException(
                sprintf("Node type definition file '<info>%s</info>' does not have read permissions.", $cnd_file)
            );
        }

        $cnd = file_get_contents($cnd_file);

        $session = $dm->getPhpcrSession();
        $ntm = $session->getWorkspace()->getNodeTypeManager();

        $ntm->registerNodeTypesCnd($cnd);
        $output->write(sprintf('Sucessfully registered node types from "<info>%s</INFO>"', $cnd_file) . PHP_EOL);
    }
}
