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
            ->addArgument('company_id', InputArgument::REQUIRED, 'Company ID')
            ->addOption('actual-month', 'a', InputOption::VALUE_NONE, 'If set, the closing will run for the actual month')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mode = ! $input->getOption('actual-month');
        $company_id = $input->getArgument('company_id');

        // set the companyid for the datastore
        $session = new Session();
        $session->set('company_id', $company_id);

        // get the service
        $closing_service = $this->getContainer()->get('jcs.closing');

        // run the closing
        $closing = $closing_service->run($mode, $output);
    }
}