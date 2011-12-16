<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PHPCR\Util\Console\Command\RegisterNodeTypesCommand;

use Doctrine\ODM\PHPCR\Translation\Translation;

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
EOT
        );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localeNamespace = Translation::LOCALE_NAMESPACE;
        $localeNamespaceUri = Translation::LOCALE_NAMESPACE_URI;
        $cnd = <<<CND
// register phpcr_locale namespace
<$localeNamespace='$localeNamespaceUri'>
// register phpcr namespace
<phpcr='http://www.doctrine-project.org/projects/phpcr_odm'>
[phpcr:managed]
  mixin
  - phpcr:class (STRING)
CND
        ;

        $session = $this->getHelper('phpcr')->getSession();

        // automatically overwrite - we are inside our phpcr namespace, nothing can go wrong
        $this->updateFromCnd($input, $output, $session, $cnd, true);

        $output->write(PHP_EOL.sprintf('Successfully registered system node types.') . PHP_EOL);
    }
}
