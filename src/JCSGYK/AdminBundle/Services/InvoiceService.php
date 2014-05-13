<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Catering;
use JCSGYK\AdminBundle\Entity\ClientOrder;

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
            throw new HttpException(500, 'Invalid Client');
        }
        if ($start_date > $end_date) {
            throw new HttpException(500, 'Invalid Start / End dates');
        }

        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        //$logger = $this->container->get('logger');

        // check the clients catering settings
        $catering = $client->getCatering();

        // list of days: key is ISO date format, when food was ordered, value is 1 or 0
        $days = $this->getMonthlySubs($catering, $start_date, $end_date);
        $costs = $this->calulateCosts($catering, $days);

        // find and check the open order-change records
        $changes = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder')->getChanges($client->getId(), $end_date);
        $changed_days = $this->getChangedDays($changes);

        // if we have no data, we create no invoice
        if (empty($days) && empty($changed_days)) {
            return false;
        }

        $discount = $this->calulateCosts($catering, $changed_days);

        // items on the invoice
        $items = [
            ['name' => 'Ebéd rendelés', 'value' => $costs],
        ];
        // if we have a discount
        if ($discount) {
            $items[] = ['name' => 'Jóváírások', 'value' => $discount];
        }

        // sum up (discount is negative!)
        $sum = $costs + $discount;

        // create the new invoice
        $invoice = new Invoice();
        $invoice->setCompanyId($company_id);
        $invoice->setClient($client);
        $invoice->setStartDate($start_date);
        $invoice->setEndDate($end_date);
        $invoice->setItems(json_encode($items));
        $invoice->setDays(json_encode($days));
        $invoice->setChanges(json_encode($changed_days));
        $invoice->setBalance(0);
        $invoice->setStatus(Invoice::READY_TO_SEND);
        $invoice->setAmount($sum);
        $invoice->setCreatedAt(new \DateTime());
        $invoice->setCreator($user);

        // save the new invoice
        $em->persist($invoice);

        // close the used cancel records
        foreach ($changes as $order) {
            $order->setStatus(ClientOrder::CLOSED);
        }

        $em->flush();

        return $invoice;
    }

    /**
     * Return an array with the changed days
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $catering
     * @param array of ClientOrder $changes
     * @return array
     */
    public function getChangedDays($changes)
    {
        $changed_days = [];
        foreach ($changes as $order) {
            // change reorder value to 2 instead of 1
            // value 1 is the normal order in the form
            $changed_days[$order->getDate()->format('Y-m-d')] = $order->getChange() == ClientOrder::REORDER ? 2 : -1;
        }

        return $changed_days;
    }

    /**
     * Returns a list of days: key is ISO date format, when food was ordered, value is 1 or 0
     * Holidays are also taken into account
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $catering
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return array
     */
    public function getMonthlySubs(Catering $catering, \DateTime $start_date, \DateTime $end_date)
    {
        $subs = $catering->getSubscriptions();
        if (empty($subs)) {
            $subs = [];
        }
        // food on every way of the week?
        $all_days = count($subs) == 7;
        // get the holidays
        $holidays = $this->container->get('jcs.ds')->getHolidays($start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
//        var_dump($holidays);

        $days = [];

        // normalize the weekly subscriptions, to have every day in the array as 1 or 0
        $week = array_replace([0, 0, 0, 0, 0, 0, 0], array_map('intval', $subs));

        // calculate the required days based on the weekly subscription
        $act_date = $start_date;
        // loop through the month
        while ($act_date <= $end_date) {
            // get the day of week of the week (0 - monday, 6 - sunday)
            $day_of_week = $act_date->format('N') - 1;
            $act_iso_date = $act_date->format('Y-m-d');
            // check for subscription
            // also check for holidays
            // if he orders for every days of the week, then he gets lunch even on holidays
            // otherwise he won't get food on holidays and resting days, but gets foon on saturdays if its a workday
            if ($all_days || (!empty($week[$day_of_week]) && empty($holidays[$act_iso_date])) || (!empty($holidays[$act_iso_date]) && $holidays[$act_iso_date] == 2)) {
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
     * @param array $days (cancels have -1 value)
     */
    private function calulateCosts(Catering $catering, array $days)
    {
        $cost = 0;

        foreach ($days as $date => $status) {
            // get the actual catering costs table
            // this runs a query for every day. Maybe not necessary...
            $table = $this->container->get('jcs.ds')->getOption('cateringcosts',$date);
            // check the cost for the day
            $daily_cost = $this->getCostForADay($catering, $table);
            if (!is_null($daily_cost)) {
                $dir = $status == ClientOrder::REORDER ? 1 : -1;
                $cost += $daily_cost * $dir;
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