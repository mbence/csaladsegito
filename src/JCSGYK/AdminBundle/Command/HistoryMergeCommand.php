<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use JCSGYK\AdminBundle\Entity\History;

class HistoryMergeCommand extends ContainerAwareCommand
{
    private $queryLimit = 1000;

    protected function configure()
    {
        $this
            ->setName('jcs:history_merge')
            ->setDescription('Merge History data from created_at fields')
            ->addArgument('company', InputArgument::REQUIRED, 'Company ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* This must never run again!

        $company_id = $input->getArgument('company');

        // set the companyid for the datastore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);

        $em = $this->getContainer()->get('doctrine')->getManager();


        $n = 0;
        $events = $this->getEvents();
        while (!empty($events)) {
            foreach ($events as $event) {
                // create a history entry for each event creation
                $user = $event->getCreator();
                $hash = sprintf('Event-%s', $event->getId());
                $l_event = 'Event insert';
                $data = null;
                $date = $event->getCreatedAt();
                $this->log($user, $hash, $l_event, $data, $date);
                $n ++;
            }
            $output->write(date('H:i:s: ') . $n . ' Event updated', true);

            $em->flush();

            // get the next batch
            $events = $this->getEvents();
        }

        $em->flush();
        $em->clear();
        $n = 0;

        // problems
        $problems = $this->getProblems();
        while (!empty($problems)) {
            foreach ($problems as $problem) {
                // create a history entry for each event creation
                $user = $problem->getCreator();
                $hash = sprintf('Problem-%s', $problem->getId());
                $l_event = 'Problem insert';
                $data = null;
                $date = $problem->getCreatedAt();
                $this->log($user, $hash, $l_event, $data, $date);
                $n ++;
            }
            $output->write(date('H:i:s: ') . $n . ' Problem updated', true);

            $em->flush();

            // get the next batch
            $problems = $this->getProblems();
        }

        $em->flush();
        $em->clear();
        $n = 0;

        // clients
        $clients = $this->getClients();
        while (!empty($clients)) {
            foreach ($clients as $client) {
                // create a history entry for each event creation
                $user = $client->getCreator();
                $hash = sprintf('Client-%s', $client->getId());
                $l_event = 'Client insert';
                $data = null;
                $date = $client->getCreatedAt();
                $this->log($user, $hash, $l_event, $data, $date);
                $n ++;
            }
            $output->write(date('H:i:s: ') . $n . ' Client updated', true);

            $em->flush();

            // get the next batch
            $clients = $this->getClients();
        }

        $em->flush();
        $em->clear();

        $output->write(date('H:i:s: ') . 'Processing finished', true);
         */
    }

    private function getEvents()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // get all the events, that have no history records
        return $em->createQuery("SELECT e FROM JCSGYKAdminBundle:Event e LEFT JOIN JCSGYKAdminBundle:History h WITH h.hash = CONCAT('Event-', e.id) WHERE h.id IS NULL")
            ->setMaxResults($this->queryLimit)
            ->getResult();
    }

    private function getProblems()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // get all the problems, that have no history records
        return $em->createQuery("SELECT p FROM JCSGYKAdminBundle:Problem p LEFT JOIN JCSGYKAdminBundle:History h WITH h.hash = CONCAT('Problem-', p.id) WHERE h.id IS NULL")
            ->setMaxResults($this->queryLimit)
            ->getResult();
    }

    private function getClients()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // get all the problems, that have no history records
        return $em->createQuery("SELECT c FROM JCSGYKAdminBundle:Client c LEFT JOIN JCSGYKAdminBundle:History h WITH h.hash = CONCAT('Client-', c.id) WHERE h.id IS NULL")
            ->setMaxResults($this->queryLimit)
            ->getResult();
    }

    private function log($user, $hash, $event, $data, $date)
    {
        $em   = $this->getContainer()->get('doctrine')->getManager();
        $ds   = $this->getContainer()->get('jcs.ds');

        $log = new History();
        $log->setCompanyId($ds->getCompanyId());
        $log->setHash($hash);
        $log->setEvent($event);
        $log->setData($data);
        $log->setUser($user);
        $log->setCreatedAt($date);

        $em->persist($log);
    }
}
