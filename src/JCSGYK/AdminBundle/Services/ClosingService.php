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

        return $em->createQuery("SELECT c FROM JCSGYKAdminBundle:MonthlyClosing c WHERE c.companyId = :company_id ORDER BY c.startDate DESC, c.createdAt DESC")
            ->setParameter('company_id', $company_id)
            ->setMaxResults(20)
            ->getResult();
    }

    public function run()
    {
        $em = $this->container->get('doctrine')->getManager();
        $sec = $this->container->get('security.context');
        $user = $sec->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $summary = '';

        // set the start / end dates
        // start date is next months first day
        $start = (new \DateTime())->modify('first day of next month');
        $end = (new \DateTime())->modify('last day of next month');
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

        // create the invoices
        $invoices = [];
        $invocie_service = $this->container->get('jcs.invoice');
        foreach ($clients as $client) {
            $invoice = $invocie_service->create($client, clone $start, clone $end);
            if (!empty($invoice)) {
                $invoices[] = $invoice;
            }
        }
        $summary .= sprintf("%s: %s db számla kiállítva \n", date('H:i:s'), count($invoices));

        // create the EcoSTAT files
        $summary .= sprintf("%s: EcoStat fájlok létrehozva \n", date('H:i:s'));

        // Send the EcoSTAT files to bookkeeping

        // update the closing record
        $summary .= sprintf("%s: Befejezve\n", date('H:i:s'));
        $closing->setSummary($summary);
        $closing->setStatus(MonthlyClosing::SUCCESS);
        $em->flush();

        return $closing;
    }
}