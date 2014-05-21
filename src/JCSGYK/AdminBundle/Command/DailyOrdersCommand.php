<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class DailyOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('jcs:orders')
            ->setDescription('Run the Daily Orders')
            ->addArgument('company', InputArgument::REQUIRED, 'Company ID')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Set the user id of the closing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $company_id = $input->getArgument('company');
        $user_id = $input->getOption('user');

        // set the companyid for the datastore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);
        if (!empty($user_id)) {
            $session->set('user_id', $user_id);
        }

        // get the service
        $dailyorders_service = $this->getContainer()->get('jcs.orders');

        // normally we only run the order for the next day
        $dates = [];
        $day_of_week = date('N');

        if ($day_of_week < 4) {
            $dates[] = new \DateTime('tomorrow');
        }
        elseif ($day_of_week == 4) {
            // but on thursday we also order for the weekend
            $dates[] = new \DateTime('tomorrow');
            $dates[] = new \DateTime('tomorrow +1 day');
            $dates[] = new \DateTime('tomorrow +2 day');
        }
        elseif ($day_of_week == 5) {
            // and on friday we order for the next monday
            $dates[] = new \DateTime('tomorrow +2 day');
        }
        // we wont do anyting on the weekends

        // run the process
        foreach ($dates as $date) {
            $dailyorders_service->run($output, $date);
            // get some sleep
            sleep(1);
        }
    }
}