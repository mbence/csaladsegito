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
        $orders_repo = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder');

        //$logger = $this->container->get('logger');

        // check the clients catering settings
        $catering = $client->getCatering();

        // save new order days in clientOrder
        $this->saveDays($client, $start_date, $end_date);

        // get all open orders for the given month and before
        $orders = $orders_repo->getOrders($client->getId(), $end_date);
        // if we have no data, we create no invoice
        if (empty($orders)) {
            return false;
        }

        $days = $this->getOrderDays($orders);

        // calculate the costs
        $costs = $this->calulateCosts($catering, $orders);

        // items on the invoice
        $items = [
            ['name' => 'Ebéd rendelés', 'value' => $costs['costs']],
        ];
        // if we have a discount
        if ($costs['discounts']) {
            $items[] = ['name' => 'Jóváírások', 'value' => $costs['discounts']];
        }

        // sum up (discount is negative!)
        $sum = $costs['costs'] + $costs['discounts'];

        // create the new invoice
        $invoice = new Invoice();
        $invoice->setCompanyId($company_id);
        $invoice->setClient($client);
        $invoice->setStartDate($start_date);
        $invoice->setEndDate($end_date);
        $invoice->setItems(json_encode($items));
        $invoice->setDays(json_encode($days));
        $invoice->setBalance(0);
        $invoice->setStatus(Invoice::READY_TO_SEND);
        $invoice->setAmount($sum);
        $invoice->setCreatedAt(new \DateTime());
        $invoice->setCreator($user);

        // save the new invoice
        $em->persist($invoice);

        // close the used cancel records
        foreach ($orders as $order) {
            $order->setClosed(true);
        }

        $em->flush();

        return $invoice;
    }

    /**
     * Return an array with the changed days
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $catering
     * @param array of ClientOrder $changes
     * @return int
     */
    public function getOrderDays($orders)
    {
        $changed_days = [];
        foreach ($orders as $order) {
            $o = 0;
            // check if ordered
            if ($order->getOrder() == true) {
                $o = 1;
                if ($order->getCancel() == true) {
                    $o = -1;
                }

                $changed_days[$order->getDate()->format('Y-m-d')] = $o;
            }
        }

        return $changed_days;
    }

    /**
     * Returns a list of days: key is ISO date format, when food was ordered, value is 1 or -1 at cancels
     * Holidays are also taken into account
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $catering
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return int
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
        $act_date = clone $start_date;
        // loop through the month
        while ($act_date <= $end_date) {
            // get the day of week of the week (0 - monday, 6 - sunday)
            $day_of_week = $act_date->format('N') - 1;
            $act_iso_date = $act_date->format('Y-m-d');
            // check for subscription
            // also check for holidays
            // otherwise he won't get food on holidays and resting days, but gets foon on saturdays if its a workday
            if ($all_days             // if he orders for every days of the week, then he gets lunch even on holidays
                    || (!empty($week[$day_of_week]) && empty($holidays[$act_iso_date])) // of if her ordered for a particular day, and it is not a holiday
                    || (count($subs) > 0 && !empty($holidays[$act_iso_date]) && $holidays[$act_iso_date] == 2))  // or if he ordered anything for the week, and the day is an extra workday
            {
                $days[$act_date->format('Y-m-d')] = 1;
            }
            // go to the next day
            $act_date->add(new \DateInterval('P1D'));
        }

        return $days;
    }

    /**
     * Calculates the costs and discounts for the client
     *
     * @param \JCSGYK\AdminBundle\Services\Catering $catering
     * @param array $orders (cancels have -1 value)
     */
    private function calulateCosts(Catering $catering, array $orders)
    {
        $costs = 0;
        $discounts = 0;

        foreach ($orders as $order) {
            $date = $order->getDate()->format('Y-m-d');
            // get the actual catering costs table
            // this runs a query for every day. Maybe not necessary...
            $table = $this->container->get('jcs.ds')->getOption('cateringcosts', $date);
            // check the cost for the day
            $daily_cost = $this->getCostForADay($catering, $table);
            if (!is_null($daily_cost)) {
                // if he has ordered for this day
                if ($order->getOrder()) {
                    if ($order->getCancel()) {
                        // if ordered but later cancelled, we add it only to the discounts
                        $discounts -= $daily_cost;
                    }
                    else {
                        // if ordered but no cancel
                        $costs += $daily_cost;
                    }
                }
                // we dont deal with records at all, where there was no order
            }
            else {
                // no match in the catering cost tables, now what?
            }
        }

        return ['costs' => $costs, 'discounts' => $discounts];
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

    /**
     * Save a list of days to ClientOrder
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @param array $days
     */
    public function saveDays(Client $client, $start_date, $end_date)
    {
        $em = $this->container->get('doctrine')->getManager();
        $sec = $this->container->get('security.context');
        $user = $sec->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $orders_repo = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder');

        // list of days: key is ISO date format, when food was ordered, value is 1 or 0
        // based on order template
        $days = $this->getMonthlySubs($client->getCatering(), $start_date, $end_date);

        // find the already created open records for this time period
        $orders = $orders_repo->getOrdersForPeriod($client->getId(), $start_date, $end_date);
 
        foreach ($days as $ISO_date => $sub) {
            // only deal with orders at the moment
            if ($sub == 1) {
                $date = new \DateTime($ISO_date);
                // check if we have any records for that day
                if (!empty($orders[$ISO_date])) {
                    $order = $orders[$ISO_date];

                    if ($order->getClosed() == false // make sure, that we only modify open records!
                        && $order->getCancel() == false // and we also skip records, that are already cancelled
                    ) {
                        $order->setOrder(true);
                    }
                }
                // or if no record exists, we create a new one
                else {
                    // no record for this day, lets create one!
                    $order = new ClientOrder();
                    $order->setCompanyId($company_id);
                    $order->setClient($client);
                    $order->setDate($date);
                    $order->setOrder(true);
                    $order->setCancel(false);
                    $order->setClosed(false);
                    $order->setCreator($user);

                    $em->persist($order);
                }
            }
        }

        $em->flush();
    }

    /**
     * Exports the unsent invoices to EcoStat
     */
    public function export()
    {
        // find the unsent invocies

        // create the data arrays

        // export to files

        // send the files
    }
}
