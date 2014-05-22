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

    /** Datastore */
    private $ds;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->ds = $this->container->get('jcs.ds');
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

        $company_id = $this->ds->getCompanyId();
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->ds->getUser();
        $orders_repo = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder');

        //$logger = $this->container->get('logger');

        // check the clients catering settings
        $catering = $client->getCatering();

        // save new order days in clientOrder
        $this->saveDays($client, $start_date, $end_date);

        // get all open orders for the given month and before
        $orders = $orders_repo->getOrders($client->getId(), $end_date);
        $days = $this->getOrderDays($orders);

        // if we have no data, we create no invoice
        if (!empty($orders) && !empty($days)) {

            // calculate the costs
            $items = $this->calulateItems($catering, $orders);

            // sum up (discount is negative!)
            $sum = 0;
            foreach ($items as $item) {
                $sum += $item['value'];
            }

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
        }
        else {
            $invoice = false;
        }

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
        $holidays = $this->ds->getHolidays($start_date->format('Y-m-d'), $end_date->format('Y-m-d'));
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
    private function calulateItems(Catering $catering, array $orders)
    {
        $items = [];
        $ratio = 1;
        $discount_text = '';

        // check discount (50% - 100%)
        if (!empty($catering->getDiscount())) {
            $discount = $catering->getDiscount();
            if (is_numeric($discount) && $discount >= 0 && $discount <= 100) {
                $ratio = (100 - $discount) / 100;
                $discount_text = " (Mérséklés {$discount}%)";
            }
        }

        foreach ($orders as $order) {
            $date = $order->getDate()->format('Y-m-d');
            // get the actual catering costs table
            // this runs a query for every day. Maybe not necessary...
            $table = $this->ds->getOption('cateringcosts', $date);
            // check the cost for the day
            $daily_cost = $this->getCostForADay($catering, $table);
            if (!is_null($daily_cost)) {
                // apply the discount
                $daily_cost = round($daily_cost * $ratio);
                // if he has ordered for this day
                if ($order->getOrder()) {
                    if ($order->getCancel()) {
                        // if ordered but later cancelled, we add it only to the discounts
                        $daily_cost *= -1;
                    }
                    if (!isset($items[$daily_cost])) {
                        $items[$daily_cost] = [
                            'name' => ($daily_cost > 0 ? 'Ebéd rendelés' : 'Jóváírás') . $discount_text,
                            'quantity' => 1,
                            'unit_price' => $daily_cost,
                            'value' => $daily_cost,
                        ];
                    }
                    else {
                        $items[$daily_cost]['quantity']++;
                        $items[$daily_cost]['value'] += $daily_cost;
                    }
                }
                // we dont deal with records at all, where there was no order
            }
            else {
                // no match in the catering cost tables, now what?
            }
        }

        return $items;
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
        $user = $this->ds->getUser();
        $company_id = $this->ds->getCompanyId();
        $orders_repo = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder');
        $catering = $client->getCatering();

        // list of days: key is ISO date format, when food was ordered, value is 1 or 0
        // based on order template
        $days = $this->getMonthlySubs($catering, $start_date, $end_date);

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
                    if ($catering->getIsActive()) {
                        $order->setOrder(true);
                        $order->setCancel(false);
                        $order->setClosed(false);
                    }
                    else {
                        // for inactive clients we set a cancelled record
                        $order->setOrder(false);
                        $order->setCancel(true);
                        $order->setClosed(false);
                    }
                    $order->setCreator($user);

                    $em->persist($order);
                }
            }
        }

        $em->flush();
    }

    /**
     * Returns the invoices of the company
     * @param int $company_id
     * @param int $limit
     * @param int $offset
     * @param int $status
     * @return array of JCSGYK\AdminBundle\Entity\Invoice
     */
    public function getInvoices($company_id, $limit = 100, $offset = 0, $status = Invoice::READY_TO_SEND)
    {
        $em = $this->container->get('doctrine')->getManager();
        return $em->createQuery("SELECT i, c FROM JCSGYKAdminBundle:Invoice i JOIN i.client c WHERE i.companyId = :company_id AND i.status = :status")
            ->setParameter('company_id', $company_id)
            ->setParameter('status', $status)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getResult();
    }

    /**
     * Update client balance
     * @param \JCSGYK\AdminBundle\Entity\Catering $catering
     */
    public function updateBalance(Catering $catering)
    {
        $balance = 0;

        $em = $this->container->get('doctrine')->getManager();
        // calculate the balance of the open invoices
        $res = $em->createQuery("SELECT SUM(i.amount - i.balance) as balance FROM JCSGYKAdminBundle:Invoice i WHERE i.client = :client AND i.status = :status")
            ->setParameter('client', $catering->getClient())
            ->setParameter('status', Invoice::OPEN)
            ->getSingleResult();

        if (isset($res['balance'])) {
            $balance = $res['balance'];
        }
        $catering->setBalance($res['balance']);
        $em->flush();

        return $balance;
    }
}
