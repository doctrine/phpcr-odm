<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateWorkSpaceCommand extends Command
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('odm:phpcr:workspace:create')
            ->addArgument('name', InputArgument::REQUIRED, 'A workspace name')
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $session = $this->getHelper('phpcr')->getSession();

        $name = $input->getArgument('name');

        $session->getWorkspace()->createWorkspace($name);

        $output->writeln("The workspace '$name' created.");
    }
}
