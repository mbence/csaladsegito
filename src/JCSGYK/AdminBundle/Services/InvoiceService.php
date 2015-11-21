<?php

namespace JCSGYK\AdminBundle\Services;

use JCSGYK\AdminBundle\Entity\HomehelpMonth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\ORM\EntityManager;

use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Catering;
use JCSGYK\AdminBundle\Entity\ClientOrder;
use JCSGYK\AdminBundle\Entity\Club;
use JCSGYK\AdminBundle\Entity\ClientOrderRepository;
use JCSGYK\AdminBundle\Entity\MonthlyClosing;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\HomeHelp;

/**
 * Invoice related service
 */
class InvoiceService
{
    /** Service container */
    private $container;

    /** @var Datastore */
    private $ds;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        /** @var DataStore ds */
        $this->ds = $this->container->get('jcs.ds');
    }

    /**
     * Create an invoice record for the Client in the given timespan
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @param $closing_type 1 monthly, 2 daily or 3 home-help
     * @return bool|\JCSGYK\AdminBundle\Entity\Invoice
     */
    public function create(Client $client, \DateTime $start_date, \DateTime $end_date, $closing_type)
    {
        if (empty($client)) {
            throw new HttpException(500, 'Invalid Client');
        }
        if ($start_date > $end_date) {
            throw new HttpException(500, 'Invalid Start / End dates');
        }

        $user = $this->ds->getUser();
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        /** @var ClientOrderRepository $orders_repo */
        $orders_repo = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder');

        if (MonthlyClosing::HOMEHELP == $closing_type) {
            list($days, $hm_clients) = $this->getHomeHelpDays($client, $start_date, $end_date);
            // calculate the invoice items
            $items = $this->calculateHomehelpItems($client, $days, $start_date, $end_date);

        } else {
            list($orders, $days) = $this->getCateringOrders($client, $start_date, $end_date);
            // calculate the invoice items
            $items = $this->calculateCateringItems($client, $orders);
        }

        // if we have no data, we create no invoice
        if (!empty($items)) {
            // sum up (discount is negative!)
            $sum = 0;
            foreach ($items as $item) {
                $sum += $item['value'];
            }
            // sum is always round
            $sum = round($sum);

            // create the new invoice
            $invoice = (new Invoice())
                ->setCompanyId($client->getCompanyId())
                ->setClient($client)
                ->setStartDate($start_date)
                ->setEndDate($end_date)
                ->setItems(json_encode($items))
                ->setDays(json_encode($days))
                ->setBalance(0)
                ->setStatus(Invoice::READY_TO_SEND)
                ->setAmount($sum)
                ->setCreatedAt(new \DateTime())
                ->setCreator($user)
                ->setInvoicetype($closing_type)
            ;

            // save the new invoice
            $em->persist($invoice);
        }
        else {
            $invoice = false;
        }

        if (MonthlyClosing::HOMEHELP != $closing_type) {
            // close the used order records
            $orders_repo->closeOrders($orders);
        } else {
            // close the Homhelp Month for this client
            foreach ($hm_clients as $hm_client) {
                $hm_client->setIsClosed(true);
            }
        }

        $em->flush();

        return $invoice;
    }

    /**
     * Return an array with the changed days
     *
     * @param array of ClientOrder $changes
     * @return int
     */
    public function getOrderDays($orders)
    {
        $changed_days = [];
        foreach ($orders as $order) {
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
     * @param Catering $catering
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
                    || (!empty($week[$day_of_week]) && empty($holidays[$act_iso_date]))) // of if her ordered for a particular day, and it is not a holiday
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
     * @param Client $client
     * @param array $orders (cancels have -1 value)
     * @return array
     */
    private function calculateCateringItems(Client $client, array $orders)
    {
        $items = [];

        if (!empty($orders)) {
            $discount_ratio = 0;
            $vat = $this->ds->getVat();
            $catering = $client->getCatering();

            // check discount (50% - 100%)
            if (!empty($catering->getDiscount())) {
                $discount = $catering->getDiscount();
                if (is_numeric($discount) && $discount >= 0 && $discount <= 100) {
                    $discount_ratio = $discount / 100;
                }
            }

            foreach ($orders as $order) {
                $date = $order->getDate()->format('Y-m-d');
                // is it a weekday or weekend
                $weekday = $order->getDate()->format('N') < 6;
                // get the actual catering costs table
                // this runs a query for every day. Maybe not necessary...
                $table = $this->ds->getOption('cateringcosts', $date);
                // check the cost for the day
                $daily_cost = $this->getCostForADay($catering, $table);

                $packaging_cost = 81;
                $delivery_cost = 64;
                $net_packaging_cost = round($packaging_cost / (1 + $vat));
                $net_delivery_cost = round($delivery_cost / (1 + $vat));


                if (!is_null($daily_cost)) {
                    // this is a gross cost, so we must reduce it to net
                    $net_cost = number_format($daily_cost / (1 + $vat), 4, '.', '');

                    // check if he has discount for this day
                    $discount_is_active = $catering->discountIsActive($order->getDate());

                    // if he has ordered for this day
                    if ($order->getOrder()) {

                        // add the discount item to every day when order is set
                        if ($discount_is_active && !$order->getCancel()) {
                            // discount is 1 item with 50% or 100% unit price and total amount are the same
                            $discount_net = -1 * $net_cost * $discount_ratio;
                            $discount_gross = -1 * $daily_cost * $discount_ratio;

                            if (!isset($items['discount'])) {
                                $items['discount'] = [
                                    'name'       => sprintf('%s mérséklés', $discount . '%'),
                                    'quantity'   => 1,
                                    'unit'       => 'db',
                                    'net_price'  => $discount_net,
                                    'unit_price' => $discount_gross,
                                    'value'      => $discount_gross,
                                    'net_value'  => $discount_net,
                                ];
                            } else {
                                $items['discount']['value'] += $discount_gross;
                                $items['discount']['unit_price'] += $discount_gross;
                                $items['discount']['net_value'] += $discount_net;
                                $items['discount']['net_price'] += $discount_net;
                            }
                        }

                        if ($order->getCancel()) {
                            // if ordered but later cancelled, we add it only to the discounts
                            $daily_cost *= -1;
                            $net_cost *= -1;
                            $packaging_cost *= -1;
                            $delivery_cost *= -1;
                            $net_packaging_cost *= -1;
                            $net_delivery_cost *= -1;

                            // if cancelled and he had a discount for this day, we only add the real amount he actually payed before
                            if ($discount_is_active) {
                                $daily_cost = $daily_cost - ($daily_cost * $discount_ratio);
                                $net_cost = $net_cost - ($net_cost * $discount_ratio);
                            }
                        }
                        if (!isset($items[$daily_cost])) {
                            $items[$daily_cost] = [
                                'name'             => $order->getCancel() ? 'Jóváírás' : 'Ebéd rendelés',
                                'quantity'         => 1,
                                'net_price'        => $net_cost,
                                'unit_price'       => $daily_cost,
                                'value'            => $daily_cost,
                                'net_value'        => $net_cost,
                                'weekday_quantity' => 0,
                            ];
                        } else {
                            $items[$daily_cost]['quantity']++;
                            $items[$daily_cost]['value'] += $daily_cost;
                            $items[$daily_cost]['net_value'] += $net_cost;
                        }
                        if ($weekday) {
                            $items[$daily_cost]['weekday_quantity']++;
                        }

                        // add the packaging and delivery costs
                        $delivery = $catering->getDelivery();

                        // no new items needed if not set, or local delivery
                        if ((593 == $delivery && !$weekday) || in_array($delivery, [594, 595, 597]) ) {
                            if (!isset($items[$packaging_cost])) {
                                $items[$packaging_cost] = [
                                    'name'             => $order->getCancel() ? 'Csomagolás jóváírás' : 'Csomagolás',
                                    'quantity'         => 1,
                                    'unit'             => 'db',
                                    'net_price'        => $net_packaging_cost,
                                    'unit_price'       => $packaging_cost,
                                    'value'            => $packaging_cost,
                                    'net_value'        => $net_packaging_cost,
                                    'weekday_quantity' => 0,
                                ];

                            } else {
                                $items[$packaging_cost]['quantity']++;
                                $items[$packaging_cost]['value'] += $packaging_cost;
                                $items[$packaging_cost]['net_value'] += $net_packaging_cost;
                            }
                            if ($weekday) {
                                $items[$packaging_cost]['weekday_quantity']++;
                            }

                            // add delivery costs for home delivey but only on weekdays
                            if (595 == $delivery) {
                                if (!isset($items[$delivery_cost])) {
                                    $items[$delivery_cost] = [
                                        'name'             => $order->getCancel() ? 'Házhoz szállítás jóváírás' : 'Házhoz szállítás',
                                        'quantity'         => 1,
                                        'unit'             => 'db',
                                        'net_price'        => $net_delivery_cost,
                                        'unit_price'       => $delivery_cost,
                                        'value'            => $delivery_cost,
                                        'net_value'        => $net_delivery_cost,
                                        'weekday_quantity' => 0,
                                    ];

                                } else {
                                    $items[$delivery_cost]['quantity']++;
                                    $items[$delivery_cost]['value'] += $delivery_cost;
                                    $items[$delivery_cost]['net_value'] += $net_delivery_cost;
                                }
                                if ($weekday) {
                                    $items[$delivery_cost]['weekday_quantity']++;
                                }
                            }
                        }
                    }
                    // we dont deal with records at all, where there was no order
                } else {
                    // no match in the catering cost tables, now what?
                }
            }
        }

        return $items;
    }

    /**
     * Calculates the costs and discounts for the client for the homehelp closing
     *
     * @param Client $client
     * @param array $days (date => hours)
     * @param \DateTime $start
     * @param \DateTime $end
     * @return array
     */
    private function calculateHomehelpItems(Client $client, array $days, \DateTime $start, \DateTime $end)
    {
        $sum = 0;
        $items = [];

        if (!empty($days)) {
            $discount_ratio = 0;
            $vat = $this->ds->getHomeHelpVat();
            $homehelp = $client->getHomehelp();

            // check discount (50% - 100%)
            if (!empty($homehelp->getDiscount())) {
                $discount = $homehelp->getDiscount();
                if (is_numeric($discount) && $discount >= 0 && $discount <= 100) {
                    $discount_ratio = $discount / 100;
                }
            }

            // we neet to iterate through the days in case the daily cost would change in-between
            foreach ($days as $date => $hours) {
                $d = (new \DateTime($date))->setTime(0, 0, 0);
                // is it a weekday or weekend
                $weekday = $d->format('N') < 6;
                // get the actual homehelp costs table
                // this runs a query for every day. Maybe not necessary...
                $table = $this->ds->getOption('homehelpcosts', $date);
                // check the cost for the day
                $daily_cost = $this->getCostForADay($homehelp, $table);

                if (!is_null($daily_cost) && !empty($hours) && is_numeric($hours)) {
                    // this is a gross cost, so we must reduce it to net
                    $net_cost = number_format($daily_cost / (1 + $vat), 4, '.', '');

                    // check if he has discount for this day
                    $discount_is_active = $homehelp->discountIsActive($d);

                    // add the discount item to every day when order is set
                    if ($discount_is_active) {
                        // discount is 1 item with 50% or 100% unit price and total amount are the same
                        $discount_net = -1 * $net_cost * $discount_ratio * $hours;
                        $discount_gross = -1 * $daily_cost * $discount_ratio * $hours;

                        if (!isset($items['discount'])) {
                            $items['discount'] = [
                                'name'       => sprintf('%s mérséklés', $discount . '%'),
                                'quantity'   => 1,
                                'unit'       => 'db',
                                'net_price'  => $discount_net,
                                'unit_price' => $discount_gross,
                                'value'      => $discount_gross,
                                'net_value'  => $discount_net,
                            ];
                        } else {
                            $items['discount']['value'] += $discount_gross;
                            $items['discount']['unit_price'] += $discount_gross;
                            $items['discount']['net_value'] += $discount_net;
                            $items['discount']['net_price'] += $discount_net;
                        }
                        $sum += $discount_gross;
                    }

                    if (!isset($items[$daily_cost])) {
                        $items[$daily_cost] = [
                            'name'             => 'Gondozás',
                            'unit'             => 'óra',
                            'quantity'         => $hours,
                            'net_price'        => $net_cost,
                            'unit_price'       => $daily_cost,
                            'value'            => $daily_cost * $hours,
                            'net_value'        => $net_cost * $hours,
                            'weekday_quantity' => 0,
                            'visits'           => 0,
                        ];
                    } else {
                        $items[$daily_cost]['quantity'] += $hours;
                        $items[$daily_cost]['value'] += $daily_cost * $hours;
                        $items[$daily_cost]['net_value'] += $net_cost * $hours;
                    }
                    $sum += $daily_cost * $hours;
                    if ($weekday) {
                        $items[$daily_cost]['weekday_quantity']++;
                    }
                }
                // add the visits
                if (!is_null($daily_cost) && !empty($hours)) {
                    $items[$daily_cost]['visits']++;
                }
            }

            // if we have an amount lets check the global limit with the catering costs of this month
            if ($sum) {
                // homehelp-only clients' invoice can not exceed 25% of the income
                // catering + homehelp clients combined invoices can not exceed 30% of the income
                $income = $homehelp->getIncome();
                $hh_discount = 0;
                $ca_discount = 0;

                // check the homehelp-only limit
                if ($sum > $income * 0.25) {
                    $hh_discount = ceil($income * 0.25) - $sum;
                }

                // check if this client has catering
                $catering = $client->getCatering();
                if (!empty($catering)) {
                    // find the catering invoices for this month
                    $invoices = $this->getClientInvoices($client->getId(), $start, $end, [Invoice::MONTHLY, Invoice::DAILY]);
                    if (!empty($invoices)) {
                        $cat_sum = $sum;
                        foreach ($invoices as $invoice) {
                            if ($invoice->getStatus() != Invoice::CANCELLED && $invoice->getAmount() > 0) {
                                $cat_sum += $invoice->getAmount();
                            }
                        }
                        // check the limit
                        if ($cat_sum > $income * 0.3) {
                            $ca_discount = ceil($income * 0.3) - $cat_sum;
                        }
                    }
                }

                if ($hh_discount || $ca_discount) {
                    // use the bigger discount (these are negative numbers!)
                    $final_discount = $hh_discount < $ca_discount ? $hh_discount : $ca_discount;
                    $final_net_discount = number_format($final_discount / (1 + $vat), 4, '.', '');

                    // add the discount item
                    $items['discount2'] = [
                        'name'       => sprintf('Törvényi mérséklés'),
                        'quantity'   => 1,
                        'unit'       => 'db',
                        'net_price'  => $final_net_discount,
                        'unit_price' => $final_discount,
                        'value'      => $final_discount,
                        'net_value'  => $final_net_discount,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Get the catering cost for one day
     *
     * @param $in Catering or Homehelp record
     * @param array $table CateringCost table (0: from, 1: to, 2: cost, 3: is single)
     * @return int or null on failure
     */
    public function getCostForADay($in, $table)
    {
        if ($in instanceof Catering) {
            $income = $in->getIncome();
            $is_single = $in->getIsSingle();
        }
        elseif ($in instanceof HomeHelp) {
            $income = $in->getIncome();
            $is_single = false;
        }

        if (isset($income)) {
            // walk through the table and find the correct salary range
            foreach ($table as $range) {
                if ($range[0] <= $income && $range[1] >= $income && (empty($range[3]) || $is_single)) {
                    return $range[2];
                }
            }
        }

        return null;
    }

    /**
     * Save a list of days to ClientOrder
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @param $start_date
     * @param $end_date
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
            $date = new \DateTime($ISO_date);
            $status = $catering->getStatus($date);

            // only deal with orders, and if that day is not closed
            if ($sub != 1 || Catering::CLOSED == $status) {
                continue;
            }

            // check if we have any records for that day
            if (!empty($orders[$ISO_date])) {
                $order = $orders[$ISO_date];

                if ($order->getClosed() == false // make sure, that we only modify open records!
                    && $order->getCancel() == false // and we also skip records, that are already cancelled
                    && Catering::ACTIVE == $status // and we only do anything if that day is active
                ) {
                    $order->setOrder(true);
                }
            }
                // or if no record exists, we create a new one
            else {
                // no record for this day, lets create one!
                $order = (new ClientOrder())
                    ->setCompanyId($company_id)
                    ->setClient($client)
                    ->setDate($date)
                    ->setCreator($user)
                    ->setMenu($catering->getMenu())
                ;

                if (Catering::ACTIVE == $status) {
                    $order->setOrder(true);
                    $order->setCancel(false);
                    $order->setClosed(false);
                } elseif (Catering::PAUSED == $status) {
                    // for paused days we set a cancelled record
                    $order->setOrder(false);
                    $order->setCancel(true);
                    $order->setClosed(false);
                }

                $em->persist($order);
            }
        }

        $em->flush();
    }

    /**
     * Returns the invoices of the company
     * @param int $company_id
     * @param int $status
     * @return array of JCSGYK\AdminBundle\Entity\Invoice
     */
    public function getInvoices($company_id, $status = Invoice::READY_TO_SEND)
    {
        $em = $this->container->get('doctrine')->getManager();
        return $em->createQuery("SELECT i, c FROM JCSGYKAdminBundle:Invoice i JOIN i.client c WHERE i.companyId = :company_id AND i.status = :status")
            ->setParameter('company_id', $company_id)
            ->setParameter('status', $status)
            ->getResult();
    }

    /**
     * Returns the invoices of the company
     * @param int $client_id
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $types
     * @return array of JCSGYK\AdminBundle\Entity\Invoice
     */
    public function getClientInvoices($client_id, \DateTime $start, \DateTime $end, array $types)
    {
        $em = $this->container->get('doctrine')->getManager();
        return $em->createQuery("SELECT i FROM JCSGYKAdminBundle:Invoice i WHERE i.client = :client_id AND i.endDate >= :start AND i.endDate <= :end AND i.invoicetype IN (:types)")
            ->setParameter('client_id', $client_id)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('types', $types)
            ->getResult();
    }

    /**
     * Update client balance
     * @param Catering|Homehelp $source
     * @return int
     */
    public function updateBalance($source)
    {
        $balance = 0;

        // the invoice type depends on the input entity
        $types = $source instanceof Catering ? [Invoice::MONTHLY, Invoice::DAILY] : [Invoice::HOMEHELP];

        $em = $this->container->get('doctrine')->getManager();
        // calculate the balance of the open invoices
        $res = $em->createQuery("SELECT SUM(i.amount - i.balance) as balance FROM JCSGYKAdminBundle:Invoice i WHERE i.client = :client AND i.status IN (:status) AND i.invoicetype IN (:types)")
            ->setParameter('client', $source->getClient())
            ->setParameter('status', [Invoice::READY_TO_SEND, Invoice::OPEN])
            ->setParameter('types', $types)
            ->getSingleResult();

        if (isset($res['balance'])) {
            $balance = $res['balance'];
        }
        $source->setBalance($res['balance']);
        $em->flush();

        return $balance;
    }


    /**
     * Update client balances
     * @param $closing_type
     */
    public function bulkUpdateBalance($closing_type)
    {
        $em = $this->container->get('doctrine')->getManager();

        if (MonthlyClosing::HOMEHELP == $closing_type) {
            $types = [Invoice::HOMEHELP];
            $dql = "UPDATE JCSGYKAdminBundle:Homehelp h SET h.balance = (SELECT SUM(i.amount - i.balance) FROM JCSGYKAdminBundle:Invoice i WHERE i.client = h.client AND i.status IN (:status) AND i.invoicetype IN (:types))";
        } else {
            $types = [Invoice::MONTHLY, Invoice::DAILY];
            $dql = "UPDATE JCSGYKAdminBundle:Catering a SET a.balance = (SELECT SUM(i.amount - i.balance) FROM JCSGYKAdminBundle:Invoice i WHERE i.client = a.client AND i.status IN (:status) AND i.invoicetype IN (:types))";
        }

        // calculate the balance of the open invoices
        $em->createQuery($dql)
            ->setParameter('status', [Invoice::READY_TO_SEND, Invoice::OPEN])
            ->setParameter('types', $types)
            ->execute();
        $em->flush();
    }

    /**
     * Return a list of months for the reports
     * @param int $company_id
     * @param int|array $types filter by invoicetype
     * @return array
     */
    public function getMonths($company_id, $types = null)
    {
        if (is_null($types)) {
            $types = [Invoice::MONTHLY, Invoice::DAILY];
        } elseif (!is_array($types)) {
            $types = [$types];
        }

        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        $res = $em->createQuery("SELECT DISTINCT(DATE_FORMAT(i.startDate, '%Y-%m')) as date FROM JCSGYKAdminBundle:Invoice i WHERE i.companyId = :company_id AND i.invoicetype IN (:types) ORDER BY i.startDate DESC")
                ->setParameter('company_id', $company_id)
                ->setParameter('types', $types)
                ->setMaxResults(12)
                ->getResult();

        $re = [];
        foreach ($res as $month) {
            $d                  = new \DateTime($month['date']);
            $re[$month['date']] = $ae->formatDate($d, 'ym');
        }

        return $re;
    }

    /**
     * Get the monthy catering report
     *
     * @param int $company_id
     * @param \DateTime $month
     * @param \JCSGYK\AdminBundle\Entity\Club $club
     * @param string $report slug of the report (summary or datacheck)
     * @return array
     */
    public function getCateringReport($company_id, \DateTime $month, Club $club = null, $report = null, $only_debts = false)
    {
        // clear the time part
        $month_end = $month->format('Y-m-t');
        $month_start = $month->format('Y-m-01');

        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        $data = [];
        // find all the invoices and clients of the given month
        $sql = "SELECT i, c, a FROM JCSGYKAdminBundle:Invoice i LEFT JOIN i.client c LEFT JOIN c.catering a "
                . "WHERE i.companyId = :company_id AND i.endDate >= :month_start AND i.endDate <= :month_end AND i.invoicetype IN (:types)";
        if (!empty($club)) {
            $sql .= "AND a.club = :club ";
        }

        if ($only_debts) {
            $sql .= "AND i.amount > i.balance ";
        }

        $sql .= "ORDER BY c.lastname, c.firstname, i.createdAt";

        $q = $em->createQuery($sql)
            ->setParameter('company_id', $company_id)
            ->setParameter('month_start', $month_start)
            ->setParameter('month_end', $month_end)
            ->setParameter('types', [Invoice::MONTHLY, Invoice::DAILY])
        ;
        if (!empty($club)) {
            $q->setParameter('club', $club);
        }

        $res = $q->getResult();

        // debt, invoice items, workday / weekend
        if (!empty($res)) {
            // summary
            $sums = [
                'id'            => '',
                'name'          => 'ÖSSZESEN',
                'address'       => '',
                'debt'          => 0,
                'amount'        => 0,
                'discount_days' => 0,
                'days'          => 0,
                'unit_price'    => '',
                'weekdays'      => 0,
                'cancel_id'     => '',
            ];
            // add extra fields to datacheck report
            if ('catering_datacheck' == $report) {
                $sums['income']    = '';
                $sums['is_single'] = '';
                $sums['orders']    = '';
            }

            // empty month
            $empty_month = [];
            foreach (range(1, 31) as $d) {
                $empty_month[$d] = '';
            }
            if ('catering_summary_detailed' == $report) {
                $sums['calendar'] = $empty_month;
            }

            foreach ($res as $invoice) {
                // we can skip cancelled and cancelling invoices for the catering_summary_detailed and catering_datacheck reports
                if (in_array($report, ['catering_summary_detailed', 'catering_datacheck']) &&
                        (!empty($invoice->getCancelId()) || Invoice::CANCELLED == $invoice->getStatus())) {
                    continue;
                }

                $client = $invoice->getClient();
                $catering = $client->getCatering();
                $items = json_decode($invoice->getItems(), true);
                // process items
                $discount = [];
                $costs = [];
                foreach ($items as $item) {
                    if ($item['unit_price'] >= 0) {
                        $costs = $item;
                    }
                    else {
                        $discount = $item;
                    }
                }

                // normal invoices
                $data_row = [
                    'id'            => $client->getCaseLabel(),
                    'name'          => $ae->formatName($client->getFirstname(), $client->getLastname(), $client->getTitle()),
                    'address'       => sprintf('(%s)', $ae->formatAddress('', '', $client->getStreet(), $client->getStreetType(), $client->getStreetNumber(), $client->getFlatNumber())),
                    'debt'          => $invoice->getStatus() == Invoice::OPEN && ($invoice->getAmount() - $invoice->getBalance() > 0) ? $invoice->getAmount() - $invoice->getBalance() : '',
                    'amount'        => $ae->formatCurrency2($invoice->getAmount()),
                    'discount_days' => empty($discount) ? 0 : $discount['quantity'],
                    'days'          => empty($costs) ? 0 : $costs['quantity'],
                    'unit_price'    => $ae->formatCurrency2(empty($costs) ? 0 : $costs['unit_price']),
                    'weekdays'      => empty($costs) ? 0 : $costs['weekday_quantity'],
                    'cancel_id'     => $invoice->getCancelId(),
                ];

                // cancel invoices
                if (!empty($data_row['cancel_id'])) {
                    $data_row['debt']          = 'sztornó számla';
                }

                // cancelled invoices
                if ($invoice->getStatus() == Invoice::CANCELLED) {
                    $data_row['debt'] = 'sztornózva';
                }

                // add extra fields to datacheck report
                if ('catering_datacheck' == $report) {
                    $data_row['income']    = $ae->formatCurrency2($catering->getIncome());
                    $data_row['is_single'] = $catering->getIsSingle() ? 'X' : '';
                    $data_row['orders']    = $this->ds->getSubTemplate($catering);
                }
                // extra fields for detailed report
                if ('catering_summary_detailed' == $report) {
                    $data_row['calendar'] = $empty_month;
                    $days = json_decode($invoice->getDays(), true);
                    if (is_array($days)) {
                        foreach ($days as $day => $o) {
                            $n = (new \DateTime($day))->format('j');
                            $data_row['calendar'][$n] = $o > 0 ? 'X' : '-';
                        }
                    }
                }

                // debt depends on some factors above
                if (is_numeric($data_row['debt'])) {
                    $sums['debt']   += $data_row['debt'];
                    $data_row['debt'] = $ae->formatCurrency2($data_row['debt']);
                }
                $data[] = $data_row;

                $sums['amount']     += $invoice->getAmount();
                $sums['discount_days'] += $data_row['discount_days'];
                $sums['days']       += $data_row['days'];
                $sums['weekdays']   += $data_row['weekdays'];
            }
            // format the summary debt
            $sums['debt'] = $ae->formatCurrency2($sums['debt']);
            $sums['amount'] = $ae->formatCurrency2($sums['amount']);
            // add the summary to the end of the report
            $data[] = $sums;
        }

        return $data;
    }

    /**
     * Get the monthy homehelp report
     *
     * @param int $company_id
     * @param \DateTime $month
     * @param string $report slug of the report (summary or datacheck)
     * @return array
     */
    public function getHomehelpReport($company_id, \DateTime $month, $report = null)
    {
        // clear the time part
        $month_end = $month->format('Y-m-t');
        $month_start = $month->format('Y-m-01');

        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        $data = [];
        // find all the invoices and clients of the given month
        $sql = "SELECT i, c, h FROM JCSGYKAdminBundle:Invoice i JOIN i.client c JOIN c.homehelp h "
                . "WHERE i.companyId = :company_id AND i.endDate >= :month_start AND i.endDate <= :month_end AND i.invoicetype IN (:types) AND i.cancelId IS NULL AND i.status != -1 ";

        $sql .= "ORDER BY c.lastname, c.firstname, i.createdAt";

        $q = $em->createQuery($sql)
            ->setParameter('company_id', $company_id)
            ->setParameter('month_start', $month_start)
            ->setParameter('month_end', $month_end)
            ->setParameter('types', [Invoice::HOMEHELP])
        ;

        $res = $q->getResult();

        // debt, invoice items, workday / weekend
        if (!empty($res)) {
            // summary
            $sums = [
                'id'         => '',
                'name'       => 'ÖSSZESEN',
                'address'    => '',
                'debt'       => 0,
                'amount'     => 0,
                'hours'      => 0,
                'unit_price' => '',
                'weekdays'   => 0,
                'cancel_id'  => '',
                'income'     => '',
                'orders'     => '',
                'visits'     => 0,
            ];

            // empty month
            $empty_month = [];
            foreach (range(1, 31) as $d) {
                $empty_month[$d] = '';
            }
            if (in_array($report, ['homehelp_visits', 'homehelp_hours'])) {
                $sums['calendar'] = $empty_month;
            }

            foreach ($res as $invoice) {
                // we can skip cancelled and cancelling invoices for the catering_summary_detailed and catering_datacheck reports
                if (in_array($report, ['catering_summary_detailed', 'catering_datacheck']) &&
                        (!empty($invoice->getCancelId()) || Invoice::CANCELLED == $invoice->getStatus())) {
                    continue;
                }

                $client = $invoice->getClient();
                $homehelp = $client->getHomehelp();
                $items = json_decode($invoice->getItems(), true);
                // process items
                $discount = [];
                $costs = [];
                foreach ($items as $item) {
                    if ($item['unit_price'] >= 0) {
                        $costs = $item;
                    }
                    else {
                        $discount = $item;
                    }
                }

                // normal invoices
                $data_row = [
                    'id'            => $client->getCaseLabel(),
                    'name'          => $ae->formatName($client->getFirstname(), $client->getLastname(), $client->getTitle()),
                    'address'       => sprintf('(%s)', $ae->formatAddress('', '', $client->getStreet(), $client->getStreetType(), $client->getStreetNumber(), $client->getFlatNumber())),
                    'debt'          => $invoice->getStatus() == Invoice::OPEN && ($invoice->getAmount() - $invoice->getBalance() > 0) ? $invoice->getAmount() - $invoice->getBalance() : '',
                    'amount'        => $ae->formatCurrency2($invoice->getAmount()),
                    'hours'         => empty($costs) ? 0 : $costs['quantity'],
                    'unit_price'    => $ae->formatCurrency2(empty($costs) ? 0 : $costs['unit_price']),
                    'weekdays'      => empty($costs) ? 0 : $costs['weekday_quantity'],
                    'cancel_id'     => $invoice->getCancelId(),
                    'visits'        => $costs['visits'],
                ];

                // cancel invoices
                if (!empty($data_row['cancel_id'])) {
                    $data_row['debt']          = 'sztornó számla';
                }

                // cancelled invoices
                if ($invoice->getStatus() == Invoice::CANCELLED) {
                    $data_row['debt'] = 'sztornózva';
                }

                $data_row['income']    = $ae->formatCurrency2($homehelp->getIncome());
                // extra fields for detailed report
                if ('homehelp_visits' == $report) {
                    $data_row['calendar'] = $empty_month;
                    $days = json_decode($invoice->getDays(), true);
                    if (is_array($days)) {
                        foreach ($days as $day => $o) {
                            $n = (new \DateTime($day))->format('j');
                            $data_row['calendar'][$n] = $o > 0 ? 'X' : '-';
                        }
                    }
                }
                if ('homehelp_hours' == $report) {
                    $data_row['calendar'] = $empty_month;
                    $days = json_decode($invoice->getDays(), true);
                    if (is_array($days)) {
                        foreach ($days as $day => $o) {
                            $n = (new \DateTime($day))->format('j');
                            $data_row['calendar'][$n] = $o;
                        }
                    }
                }

                // debt depends on some factors above
                if (is_numeric($data_row['debt'])) {
                    $sums['debt']   += $data_row['debt'];
                    $data_row['debt'] = $ae->formatCurrency2($data_row['debt']);
                }
                $data[] = $data_row;

                $sums['amount']   += $invoice->getAmount();
                $sums['hours']    += $data_row['hours'];
                $sums['weekdays'] += $data_row['weekdays'];
                $sums['visits']   += $data_row['visits'];
            }
            // format the summary debt
            $sums['debt'] = $ae->formatCurrency2($sums['debt']);
            $sums['amount'] = $ae->formatCurrency2($sums['amount']);
            // add the summary to the end of the report
            $data[] = $sums;
        }

        return $data;
    }

    /**
     * Cancel an invoice
     * @param \JCSGYK\AdminBundle\Entity\Invoice $invoice
     */
    public function cancelInvoice(Invoice $invoice)
    {
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->ds->getUser();

        // reset the involved client order days
        $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder')->resetOrders($invoice);

        // set the invoice status to CANCELLED
        $invoice->setStatus(Invoice::CANCELLED);

        $items = $this->cancelItems($invoice->getItems());

        // create a new, negative invoice
        $new_inv = (new Invoice())
            ->setCompanyId($invoice->getCompanyId())
            ->setClient($invoice->getClient())
            ->setStartDate($invoice->getStartDate())
            ->setEndDate($invoice->getEndDate())
            ->setCancelId($invoice->getId())
            ->setItems($items)
            ->setAmount(-1 * $invoice->getAmount())
            ->setStatus(Invoice::READY_TO_SEND)
            ->setCreator($user)
            ->setCreatedAt(new \DateTime())
            ->setInvoicetype($invoice->getInvoicetype())
        ;

        $em->persist($new_inv);

    }


    /**
     * Cancel items for cancelling invoices
     * @param json encoded array $items
     * @return json encoded array
     */
    private function cancelItems($items)
    {
        $items = json_decode($items, true);

        if (!empty($items)) {
            foreach ($items as $k => $i) {
                // {"680":{"name":"Eb\u00e9d rendel\u00e9s","quantity":31,"net_price":"535.4331","unit_price":680,"value":21080,"net_value":16598.4261,"weekday_quantity":21}}
                $items[$k]['quantity']         = -1 * $items[$k]['quantity'];
                $items[$k]['value']            = -1 * $items[$k]['value'];
                $items[$k]['net_value']        = -1 * $items[$k]['net_value'];
                $items[$k]['weekday_quantity'] = -1 * $items[$k]['weekday_quantity'];
            }
        }

        return json_encode($items);
    }

    /**
     * @param Client $client
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return array
     */
    private function getCateringOrders(Client $client, \DateTime $start_date, \DateTime $end_date)
    {
        // save new order days in clientOrder
        $this->saveDays($client, $start_date, $end_date);

        // get all open orders for the given month and before
        $orders = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder')->getOrders($client->getId(), $end_date);
        $days = $this->getOrderDays($orders);

        return array($orders, $days);
    }

    /**
     * Get the days for the homehelp invoices
     * @param Client $client
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return array
     */
    private function getHomeHelpDays(Client $client, \DateTime $start_date, \DateTime $end_date)
    {
        $days = [];

        $client_id = $client->getId();
        // get the open HomehelpMonth records for this client
        $hm_clients = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Homehelp')->getClientMonths($client_id, $start_date, $end_date, 0);

        if (!empty($hm_clients)) {
            foreach ($hm_clients as $hm_client) {
                $hh_month = $hm_client->getHomehelpMonth();

                $month = $hh_month->getDate()->format('Y-m-');
                $day_count = (int) $hh_month->getDate()->format('t');

                $row = $this->getClientRow($hh_month, $client_id);
                if (!empty($row)) {
                    // $row[0] is the client id
                    // last 2 elements in the row are summary fields
                    foreach ($row as $day => $hours) {
                        if ($day > 0 && $day <= $day_count) {
                            if (!empty($hours)) {
                                $date = (new \DateTime($month . $day))->format('Y-m-d');

                                // add to the days array
                                if (empty($days[$date])) {
                                    $days[$date] = 0;
                                }
                                if (is_numeric($hours)) {
                                    $days[$date] += $hours;
                                } else {
                                    $days[$date] = $hours;
                                }

                            }
                        }
                    }
                }
            }
            // sort the arrays
            if (!empty($days)) {
                ksort($days);
            }
        }

        return [$days, $hm_clients];
    }

    /**
     * Find the client row in the homehelp table
     * @param HomehelpMonth $hh_month
     * @param $client_id
     * @return array $row or null on failure
     */
    private function getClientRow(HomehelpMonth $hh_month, $client_id)
    {
        $row = null;
        $table_data = $hh_month->getData();
        if (!empty($table_data) && is_array($table_data)) {
            foreach ($table_data as $r) {
                if (!empty($r[0]) && $r[0] == $client_id) {
                    $row = $r;
                    break;
                }
            }
        }

        return $row;
    }



}
