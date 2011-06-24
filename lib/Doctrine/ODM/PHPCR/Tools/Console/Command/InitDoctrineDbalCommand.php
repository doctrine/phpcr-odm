<?php

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitDoctrineDbalCommand extends Command
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('odm:phpcr:init:dbal');
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $session = $this->getHelper('phpcr')->getSession();
        if (!$session instanceof \Jackalope\Session) {
            $output->write(PHP_EOL.'<error>The session option did not point to an instance of Jackalope.</error>'.PHP_EOL);
            throw new \InvalidArgumentException('The session option did not point to an instance of Jackalope.');
        }

        $transport = $session->getTransport();
        if (!$transport instanceof \Jackalope\Transport\Doctrine\DoctrineTransport) {
            $output->write(PHP_EOL.'<error>The session option did not point to an instance of Jackalope Doctrine DBAL Transport.</error>'.PHP_EOL);
            throw new \InvalidArgumentException('The session option did not point to an instance of Jackalope Doctrine DBAL Transport.');
        }

        $connection = $transport->getConnection();
        $schema = \Jackalope\Transport\Doctrine\RepositorySchema::create();
        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->exec($sql);
        }

        $session->getWorkspace()->createWorkspace('default');

        $output->writeln("Jackalope Doctrine DBAL tables have been initialized successfully and 'default' workspace created.");
    }
}
