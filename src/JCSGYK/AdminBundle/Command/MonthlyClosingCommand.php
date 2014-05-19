<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class MonthlyClosingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('jcs:closing')
            ->setDescription('Run the Monthly Closing')
            ->addArgument('company', InputArgument::REQUIRED, 'Company ID')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Set the user id of the closing')
            ->addOption('actual-month', 'a', InputOption::VALUE_NONE, 'If set, the closing will run for the actual month')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mode = ! $input->getOption('actual-month');
        $company_id = $input->getArgument('company');
        $user_id = $input->getOption('user');

        // set the companyid for the datastore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);
        if (!empty($user_id)) {
            $session->set('user_id', $user_id);
        }

        // get the service
        $closing_service = $this->getContainer()->get('jcs.closing');

        // run the closing
        $closing = $closing_service->run($mode, $output);
    }
}