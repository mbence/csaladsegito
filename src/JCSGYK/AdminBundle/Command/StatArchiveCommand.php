<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Console\Input\ArrayInput;
use JCSGYK\AdminBundle\Services\DataStore;

class StatArchiveCommand extends ContainerAwareCommand
{
    /**
     * Command config
     */
    protected function configure()
    {
        $this
            ->setName('jcs:stat')
            ->setDescription('Run the Monthly Statistics')
            ->addArgument('company', InputArgument::REQUIRED, 'Company ID')
            ->addOption('month', null, InputOption::VALUE_REQUIRED, 'Month')
            ->addOption('stat', null, InputOption::VALUE_REQUIRED, 'Id of the stat to run')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $company_id = $input->getArgument('company');
        $month = $input->getOption('month');
        $stat = $input->getOption('stat');

        // validate the input month
        if (!is_null($month) && false === strtotime($month)) {
            $output->writeln("<error>Invalid date: {$month}</error>");

            return false;
        }
        // validate stat ids
        if (!is_null($stat)) {
            $stat_ids = $this->getContainer()->get('jcs.ds')->getAllStatIds();
            if (!in_array($stat, $stat_ids)) {
                $output->writeln("<error>Invalid stat id: {$stat}</error>");

                return false;
            }
        }

        // set the company id for the dataStore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);

        // run the stats
        $this->getContainer()->get('jcs.stat_archive')->run($output, $month, $stat);
    }
}