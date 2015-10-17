<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use JCSGYK\AdminBundle\Entity\Catering;
use JCSGYK\AdminBundle\Entity\Client;

class CateringDatesFixCommand extends ContainerAwareCommand
{
    private $queryLimit = 1000;

    protected function configure()
    {
        $this
            ->setName('jcs:catering_dates_fix')
            ->setDescription('Fix the catering and home help dates (from-to)')
            ->addArgument('company', InputArgument::REQUIRED, 'Company ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $company_id = $input->getArgument('company');

        // set the companyid for the datastore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $today = new \DateTime('today');

        $tables = ['Catering', 'HomeHelp'];

        foreach ($tables as $table) {
            $n = 0;
            // get the missing catering records
            $records = $this->getRecords($table);
            while (!empty($records)) {
                foreach ($records as $record) {
                    // find the corresponding archive record
                    $archive_date = $this->findLastArchiveDate($record->getClient());
                    if (!empty($archive_date)) {
                        $record->setAgreementTo($archive_date);
                        $n ++;
                    }
                }
                $output->write(date('H:i:s: ') . $n . ' ' . $table . ' records updated', true);

                $em->flush();

                // get the next batch
                $records = $this->getRecords($table);
            }

            $n = 0;
            // fix the paused clients
            $records = $this->getPausedRecords($table);
            while (!empty($records)) {
                foreach ($records as $record) {
                    if ($record->isActive()) {
                        $record->setPausedFrom($today);
                        $n ++;
                    }
                }
                $output->write(date('H:i:s: ') . $n . ' ' . $table . ' records updated', true);

                $em->flush();

                // get the next batch
                $records = $this->getPausedRecords($table);
            }
        }


        $em->flush();
        $em->clear();

        $output->write(date('H:i:s: ') . 'Processing finished', true);
    }

    private function getRecords($table = 'Catering')
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // get all the catering records, that have no agreement to set and the client is archived
        return $em->createQuery("SELECT a FROM JCSGYKAdminBundle:{$table} a LEFT JOIN a.client c WHERE a.agreementTo IS NULL AND c.isArchived=1")
            ->setMaxResults($this->queryLimit)
            ->getResult();
    }

    private function getPausedRecords($table = 'Catering')
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // get all the catering records, that are paused but the client is not archived
        return $em->createQuery("SELECT a FROM JCSGYKAdminBundle:{$table} a LEFT JOIN a.client c WHERE a.isActive=0 AND a.pausedFrom IS NULL AND c.isArchived=0 AND (a.agreementTo IS NULL OR a.agreementTo > :today)")
            ->setParameter('today', new \DateTime('today'))
            ->setMaxResults($this->queryLimit)
            ->getResult();
    }

    private function findLastArchiveDate(Client $client)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // get the date of the last archive entry
        $res = $em->createQuery("SELECT a.createdAt FROM JCSGYKAdminBundle:Archive a WHERE a.client=:client ORDER BY a.createdAt DESC")
            ->setParameter('client', $client)
            ->setMaxResults(1)
            ->getResult();

        if (!empty($res[0]['createdAt'])) {
            return $res[0]['createdAt'];
        }
    }
}
