<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Catering;

/**
 * Invoice related service
 */
class InvoiceService
{
    /** Service container */
    private $container;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Create an invoice record for the Client in the give timespan
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     */
    public function create(Client $client, \DateTime $start_date, \DateTime $end_date)
    {
        if (empty($client)) {
            throw new HttpException('invalid Client', 500);
        }
        if ($start_date > $end_date) {
            throw new HttpException('invalid Start / End dates', 500);
        }

        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        //$logger = $this->container->get('logger');

        // check the clients catering settings
        $catering = $client->getCatering();

        // list of days: key is ISO date format, when food was ordered, value is 1 or 0
        $days = $this->getMonthlySubs($catering, $start_date, $end_date);

        // TODO: check for previous cancels

        var_dump($days);
        // do something about the holidays

        // find and check the open order-change records

        // create the new invoice

        $invoice = new Invoice();
        $invoice->setCompanyId($company_id);
        $invoice->setClient($client);
        $invoice->setStartDate($start_date);
        $invoice->setEndDate($end_date);
        $invoice->setItems(json_encode($days));
        $invoice->setBalance(0);
        $invoice->setStatus(Invoice::OPEN);
        $invoice->setAmount($this->calulateCosts($catering, $days));
        $invoice->setCreatedAt(new \DateTime());
        $invoice->setCreator($user);

        // save the new invoice
//        $em->persist($invoice);
        // close the prevoius cancel records

//        $em->flush();

        return $invoice;
    }

    /**
     * Returns a list of days: key is ISO date format, when food was ordered, value is 1 or 0
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $catering
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return int
     */
    private function getMonthlySubs(Catering $catering, \DateTime $start_date, \DateTime $end_date)
    {
        $days = [];

        // normalize the weekly subscriptions, to have every day in the array as 1 or 0
        $week = array_replace([0, 0, 0, 0, 0, 0, 0], array_map('intval', $catering->getSubscriptions()));

        // calculate the required days based on the weekly subscription
        $act_date = $start_date;
        // loop through the month
        while ($act_date <= $end_date) {
            // get the day of week of the week (0 - monday, 6 - sunday)
            $day_of_week = $act_date->format('N') - 1;
            // check for subscription
            if (!empty($week[$day_of_week])) {
                $days[$act_date->format('Y-m-d')] = 1;
            }
            // go to the next day
            $act_date->add(new \DateInterval('P1D'));
        }

        return $days;
    }

    /**
     * Calculates the cost for the client
     *
     * @param \JCSGYK\AdminBundle\Services\Catering $catering
     * @param array $days
     */
    private function calulateCosts(Catering $catering, array $days)
    {
        $cost = 0;

        foreach ($days as $date => $status) {
            // get the actual catering costs table
            // this runs a query for every day. Maybe not necessary...
            $table = $this->container->get('jcs.ds')->getCateringCosts($date);
            // check the cost for the day
            $daily_cost = $this->getCostForADay($catering, $table);
            if (!is_null($daily_cost)) {
                $cost += $daily_cost;
            }
            else {
                // no match in the catering cost tables, now what?
            }
        }

        return $cost;
    }

    /**
     * Get the catering cost for one day
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $catering record of the client
     * @param array $table CateringCost table (0: from, 1: to, 2: cost, 3: is single)
     * @return int or null on failure
     */
    private function getCostForADay(Catering $catering, $table)
    {
        $income = $catering->getIncome();
        $is_single = $catering->getIsSingle();

        // walk through the table and find the correct salary range
        foreach ($table as $range) {
            if ($range[0] <= $income && $range[1] >= $income && (empty($range[3]) || $is_single)) {
                return $range[2];
            }
        }

        return null;
    }
}