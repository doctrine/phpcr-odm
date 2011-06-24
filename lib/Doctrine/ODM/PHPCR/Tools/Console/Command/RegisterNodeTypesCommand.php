<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to load and register a node type defined in a CND file.
 *
 * See the link below for the cnd definition.
 * @link http://jackrabbit.apache.org/node-type-notation.html
 */

class RegisterNodeTypesCommand extends Command
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
            new InputOption('allow-update', '', InputOption::VALUE_NONE, 'Overwrite existig node type'),
        ))
        ->setHelp(<<<EOT
Register node types in the PHPCR repository.

This command allows to register node types in the repository that are defined
in a CND (Compact Node Definition) file.

Custom node types can be used to define the structure of content repository
nodes, like allowed properties and child nodes together with the namespaces
and their prefix used for the names of node types and properties.

If you use --allow-update existing node type definitions will be overwritten
in the repository.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getHelper('phpcr')->getDocumentManager();

        $cnd_file = realpath($input->getArgument('cnd-file'));

        if (!file_exists($cnd_file)) {
            throw new \InvalidArgumentException(
                sprintf("Node type definition file '<info>%s</info>' does not exist.", $cnd_file)
            );
        } elseif (!is_readable($cnd_file)) {
            throw new \InvalidArgumentException(
                sprintf("Node type definition file '<info>%s</info>' does not have read permissions.", $cnd_file)
            );
        }

        $cnd = file_get_contents($cnd_file);
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
        $output->write(PHP_EOL.sprintf('Successfully registered node types from "<info>%s</info>"', $cnd_file) . PHP_EOL);
    }
}
