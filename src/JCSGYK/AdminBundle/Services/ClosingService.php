<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\MonthlyClosing;

/**
 * Monthly Closing Service
 */
class ClosingService
{
    /** Service container */
    private $container;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * returns a list of the latest closing records
     * @return type
     */
    public function getList()
    {
        $em = $this->container->get('doctrine')->getManager();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        return $em->createQuery("SELECT c FROM JCSGYKAdminBundle:MonthlyClosing c WHERE c.companyId = :company_id ORDER BY c.createdAt DESC")
            ->setParameter('company_id', $company_id)
            ->setMaxResults(20)
            ->getResult();
    }

    /**
     * Start the monthly closing process
     * @param int $period 1 = normal run (next month), 0 = actual month
     * @return \JCSGYK\AdminBundle\Entity\MonthlyClosing
     */
    public function run($period = 1)
    {
        $em = $this->container->get('doctrine')->getManager();
        $sec = $this->container->get('security.context');
        $user = $sec->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $summary = '';

        // set the start / end dates
        // start date is next months first day
        if (1 == $period) {
            $start = new \DateTime('first day of next month');
            $end = new \DateTime('last day of next month');
        }
        else {
            $start = new \DateTime('first day of this month');
            $end = new \DateTime('last day of this month');
        }
        $created_at = new \DateTime();

        $summary .= "Havi zárás \n";
        $summary .= sprintf("%s - %s \n\n", $start->format('Y-m-d'), $end->format('Y-m-d'));
        $summary .= sprintf("%s: Indítva \n", $created_at->format('H:i:s'));

        // create a new closing record
        $closing = new MonthlyClosing();
        $closing->setCompanyId($company_id);
        $closing->setCreator($user);
        $closing->setCreatedAt($created_at);
        $closing->setStatus(MonthlyClosing::RUNNING);
        $closing->setStartDate($start);
        $closing->setEndDate($end);
        $closing->setSummary($summary);

        $em->persist($closing);
        $em->flush();

        // find all clients that have active subscriptions
        $clients = $em->getRepository('JCSGYKAdminBundle:Client')->getForClosing($company_id);
        $summary .= sprintf("%s: %s ügyfél lekérdezve\n", date('H:i:s'), count($clients));
        $closing->setSummary($summary);
        $em->flush();

        // create the invoices
        $invoices = [];
        $invocie_service = $this->container->get('jcs.invoice');
        foreach ($clients as $client) {
            $invoice = $invocie_service->create($client, clone $start, clone $end);
            if (!empty($invoice)) {
                $invoices[] = $invoice;
            }
        }
        if (empty($invoices)) {
            $summary .= sprintf("%s: Nincsen új megrendelés \n", date('H:i:s'));
        }
        else {
            $summary .= sprintf("%s: %s db számla kiállítva \n", date('H:i:s'), count($invoices));

            // create the EcoSTAT files
            $summary .= sprintf("%s: EcoStat fájlok létrehozva \n", date('H:i:s'));

            // Send the EcoSTAT files to bookkeeping

        }

        // update the closing record
        $summary .= sprintf("%s: Befejezve\n", date('H:i:s'));

        $closing->setSummary($summary);
        $closing->setStatus(MonthlyClosing::SUCCESS);
        $em->flush();

        return $closing;
    }
}