<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Console\Input\ArrayInput;

class StatArchiveCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('jcs:stat')
            ->setDescription('Run the Monthly Statistics')
            ->addArgument('company', InputArgument::REQUIRED, 'Company ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $company_id = $input->getArgument('company');

        // set the company id for the dataStore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);

        // run the stats
        $this->getContainer()->get('jcs.stat_archive')->run($output);
    }
}