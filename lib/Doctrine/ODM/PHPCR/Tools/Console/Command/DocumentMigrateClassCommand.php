<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\Util\Console\Command\NodesUpdateCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class DocumentMigrateClassCommand extends NodesUpdateCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:phpcr:document:migrate-class')
            ->setDescription('Update the class name stored in the repository, e.g. after refactoring.')

            ->addArgument('classname', InputArgument::REQUIRED, 'Old class name (does not need to exist in current codebase')
            ->addArgument('new-classname', InputArgument::REQUIRED, 'New class name (must exist in current codebase')
            ->setHelp(
                <<<'HERE'
The <info>doctrine:phpcr:docment:migrate-class</info> command migrates document
classes from the old class to the new class, updating the parent class
information too.

  <info>$ php ./app/console/phpcr doctrine:phpcr:document:migrate-class "Old\ClassName" "New\ClassName"</info>

Note that the command only changes the class meta information, but does <comment>not</comment>
validate whether the repository contains all required properties for your
document. If you have data changes, you additionally need to run a nodes update
command or similar to migrate data.
HERE
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // we do not want to expose the parent options, but use the arguments and
        // options to pass information to the parent.
        parent::configure();

        $classname = $input->getArgument('classname');
        $newClassname = $input->getArgument('new-classname');

        if (!class_exists($newClassname)) {
            throw new \Exception(sprintf(
                'New class name "%s" does not exist.',
                $newClassname
            ));
        }

        /** @var $documentManager DocumentManager */
        $documentManager = $this->getHelper('phpcr')->getDocumentManager();
        $mapper = $documentManager->getConfiguration()->getDocumentClassMapper();

        $input->setOption('query', sprintf(
            'SELECT * FROM [nt:base] WHERE [phpcr:class] = "%s"',
            $classname
        ));

        $input->setOption('apply-closure', [
            function ($session, $node) use ($newClassname, $documentManager, $mapper) {
                $mapper->writeMetadata($documentManager, $node, $newClassname);
            },
        ]);

        return parent::execute($input, $output);
    }
}
