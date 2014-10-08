<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Console\Input\ArrayInput;

use JCSGYK\AdminBundle\Services\ClosingService;
use JCSGYK\AdminBundle\Entity\MonthlyClosing;

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
            ->addOption('home-help', 'p', InputOption::VALUE_NONE, 'If set, the closing will run for the home-help clients')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mode = ! $input->getOption('actual-month');
        $company_id = $input->getArgument('company');
        $user_id = $input->getOption('user');
        $home_help = $input->getOption('home-help');

        // check options
        if ($home_help && !$mode) {
            $output->writeln("<error>Invalid options, can't use --home-help and --actual-month together</error>");

            return false;
        }

        // get the closing type
        if ($home_help) {
            $closing_type = MonthlyClosing::HOMEHELP;
        } elseif ($mode) {
            $closing_type = MonthlyClosing::MONTHLY;
        } else {
            $closing_type = MonthlyClosing::DAILY;
        }

        // set the companyid for the datastore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);
        if (!empty($user_id)) {
            $session->set('user_id', $user_id);
        }

        // get the service
        /** @var ClosingService $closing_service */
        $closing_service = $this->getContainer()->get('jcs.closing');

        // run the closing
        $closing_service->run($closing_type, $output);

        // start the daily orders after the daily closing
        if (MonthlyClosing::DAILY == $closing_type) {
            $command = $this->getApplication()->find('jcs:orders');

            $arguments = array(
                'command' => 'jcs:orders',
                'company'    => $company_id,
            );
            if (!empty($user_id)) {
                $arguments['--user'] = $user_id;
            }

            $input = new ArrayInput($arguments);
            $returnCode = $command->run($input, $output);
        }

    }
}