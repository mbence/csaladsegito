<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use JCSGYK\AdminBundle\Entity\Invoice;

/**
 * ClientOrderRepository
 */
class ClientOrderRepository extends EntityRepository
{
    /**
     * Finds the open orders for a given period of time for one client
     * @param int $client_id
     * @param \DateTime or string $end_date end of the invoice period
     * @param int $closed optional
     * @return array of JCSGYK\AdminBundle\Entity\ClientOrder
     */
    public function getOrders($client_id, $end_date, $closed = false)
    {
        // remove the time part of the dates
        if ($end_date instanceof \DateTime) {
            $end_date = $end_date->format('Y-m-d');
        }

        return $this->getEntityManager()
            ->createQuery("SELECT o FROM JCSGYKAdminBundle:ClientOrder o WHERE o.client = :client_id AND o.date <= :end_date AND o.closed = :closed ORDER BY o.date")
            ->setParameter('client_id', $client_id)
            ->setParameter('end_date', $end_date)
            ->setParameter('closed', $closed)
            ->getResult();
    }

    /**
     * Finds the orders for a given period of time for one client
     * @param int $client_id
     * @param \DateTime or string $start_date start of the time period
     * @param \DateTime or string $end_date end of the time period
     * @param int $closed optional
     * @return array of JCSGYK\AdminBundle\Entity\ClientOrder
     */
    public function getOrdersForPeriod($client_id, $start_date, $end_date)
    {
        // remove the time part of the dates
        if ($start_date instanceof \DateTime) {
            $start_date = $start_date->format('Y-m-d');
        }
        if ($end_date instanceof \DateTime) {
            $end_date = $end_date->format('Y-m-d');
        }
        $orders = [];
        $os = $this->getEntityManager()
            ->createQuery("SELECT o FROM JCSGYKAdminBundle:ClientOrder o WHERE o.client = :client_id AND o.date >= :start_date AND o.date <= :end_date ORDER BY o.date")
            ->setParameter('client_id', $client_id)
            ->setParameter('start_date', $start_date)
            ->setParameter('end_date', $end_date)
            ->getResult();

        if (!empty($os)) {
            // map to date string
            foreach ($os as $o) {
                $orders[$o->getDate()->format('Y-m-d')] = $o;
            }
        }

        return $orders;
    }

    /**
     * Update all future orders to the specified menu
     * @param int $client_id
     * @param \DateTime $start_date
     * @param int $new_menu
     * @return int number of changed records
     */
    public function updateMenu($client_id, $start_date, $new_menu)
    {
        return $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o SET o.menu = :menu WHERE o.client = :client_id AND o.date >= :start_date')
            ->setParameter('client_id', $client_id)
            ->setParameter('start_date', $start_date)
            ->setParameter('menu', $new_menu)
            ->execute();
    }

    /**
     * Cancel or reorder all future orders. Called when clientcatering is set to inactive/active
     *
     * @param int $client_id
     * @param \DateTime $start_date
     * @param bool $new_state 1: order, 0: cancel
     * @return int number of changed records
     */
    public function updateOrders($client_id, $start_date, $new_state)
    {
        $n = 0;

        if ($new_state) {
            // REORDER
            // $new_stat = 1
            // 011 -> 100
            // 010 -> 100
            // 111 -> 100
            $n += $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o '
                    . 'SET o.order = 1, o.cancel = 0, o.closed = 0 '
                    . 'WHERE o.client = :client_id AND o.date >= :start_date AND '
                    . '((o.order = 0 AND o.cancel = 1 AND o.closed = 0) OR '
                    . '(o.order = 0 AND o.cancel = 1 AND o.closed = 1) OR '
                    . '(o.order = 1 AND o.cancel = 1 AND o.closed = 1))')
                ->setParameter('client_id', $client_id)
                ->setParameter('start_date', $start_date)
                ->execute();
            // 110 -> 101
            $n += $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o '
                    . 'SET o.order = 1, o.cancel = 0, o.closed = 1 '
                    . 'WHERE o.client = :client_id AND o.date >= :start_date AND '
                    . 'o.order = 1 AND o.cancel = 1 AND o.closed = 0')
                ->setParameter('client_id', $client_id)
                ->setParameter('start_date', $start_date)
                ->execute();
        }
        else {
            // CANCEL
            // $new_state = 0
            // 100 -> 010
            $n += $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o '
                    . 'SET o.order = 0, o.cancel = 1, o.closed = 0 '
                    . 'WHERE o.client = :client_id AND o.date >= :start_date AND '
                    . 'o.order = 1 AND o.cancel = 0 AND o.closed = 0')
                ->setParameter('client_id', $client_id)
                ->setParameter('start_date', $start_date)
                ->execute();
            // 101 -> 110
            $n += $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o '
                    . 'SET o.order = 1, o.cancel = 1, o.closed = 0 '
                    . 'WHERE o.client = :client_id AND o.date >= :start_date AND '
                    . 'o.order = 1 AND o.cancel = 0 AND o.closed = 1')
                ->setParameter('client_id', $client_id)
                ->setParameter('start_date', $start_date)
                ->execute();
        }

        return $n;
    }

    public function getDailyOrders($company_id, $date, $end_date = null)
    {
        // if only 1 day, then we return the simple form
        if (empty($end_date)) {
            return $this->getDailyOrdersForOneDay($company_id, $date);
        }

        // remove the time part of the dates
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }
        if (empty($end_date)) {
            $end_date = $date;
        }
        elseif ($end_date instanceof \DateTime) {
            $end_date = $end_date->format('Y-m-d');
        }

        // we need separte counts for the weekdays and the weekends
        // so we list the weekend dates first
        $sd = new \DateTime($date);
        $ed = new \DateTime($end_date);

        $weekends = [];

        while ($sd <= $ed) {
            // is this a weekday or a weekend?
            if ($sd->format('N') > 5) {
                $weekends[] = $sd->format('Y-m-d');
            }
            $sd->modify('+1 day');
        }

        $results = [];
        $results['weekdays'] = $this->getEntityManager()
            ->createQuery("SELECT b.id, o.menu, COUNT(o) as orders, 0 as weekend "
                    . "FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c LEFT JOIN c.catering a JOIN a.club b "
                    . "WHERE o.companyId = :company_id AND o.order = 1 AND o.cancel = 0 "
                    . "AND o.date >= :date AND o.date <= :end_date "
                    . "AND o.date NOT IN (:weekends) "
                    . "GROUP BY a.club, o.menu")
            ->setParameter('company_id', $company_id)
            ->setParameter('date', $date)
            ->setParameter('end_date', $end_date)
            ->setParameter('weekends', $weekends)
            ->getResult();

        $results['weekends'] = $this->getEntityManager()
            ->createQuery("SELECT b.id, o.menu, COUNT(o) as orders, 1 as weekend "
                    . "FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c LEFT JOIN c.catering a JOIN a.club b "
                    . "WHERE o.companyId = :company_id AND o.order = 1 AND o.cancel = 0 "
                    . "AND o.date >= :date AND o.date <= :end_date "
                    . "AND o.date IN (:weekends) "
                    . "GROUP BY a.club, o.menu")
            ->setParameter('company_id', $company_id)
            ->setParameter('date', $date)
            ->setParameter('end_date', $end_date)
            ->setParameter('weekends', $weekends)
            ->getResult();

        // merge the weekday / weekend results arrays

        return array_merge($results['weekdays'], $results['weekends']);
    }

    /**
     * Get the orders for only one day
     *
     * @param int $company_id
     * @param \DateTime $date
     * @return array
     */
    private function getDailyOrdersForOneDay($company_id, $date)
    {
        // remove the time part of the dates
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }
        $dd = new \DateTime($date);
        // $weekend = 1 for weekends, or 0 for weekdays
        $weekend = $dd->format('N') > 5 ? 1 : 0;


        return $this->getEntityManager()
            ->createQuery("SELECT b.id, o.menu, COUNT(o) as orders, {$weekend} as weekend "
                    . "FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c LEFT JOIN c.catering a JOIN a.club b "
                    . "WHERE o.companyId = :company_id AND o.order = 1 AND o.cancel = 0 "
                    . "AND o.date = :date "
                    . "GROUP BY a.club, o.menu")
            ->setParameter('company_id', $company_id)
            ->setParameter('date', $date)
            ->getResult();
    }

    /**
     * Closes all the given orders with one update query
     * @param array of Order $orders
     * @return affected rows
     */
    public function closeOrders($orders)
    {
        return $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o SET o.closed = 1 WHERE o.id IN (:idlist)')
            ->setParameter('idlist', $orders)
            ->execute();
    }

    /**
     * Resets the orders belonging to an invoice
     * @param \JCSGYK\AdminBundle\Entity\Invoice $invoice
     */
    public function resetOrders(Invoice $invoice)
    {
        $days = json_decode($invoice->getDays(), true);
        if (!empty($days)) {
            $dates = array_keys($days);
            $n = 0;

            // select all canelled but open days
            $cancelled_days = $this->getEntityManager()->createQuery('SELECT o.id FROM JCSGYKAdminBundle:ClientOrder o WHERE '
                    . 'o.order = 1 AND o.cancel = 1 AND o.closed = 0 AND '
                    . 'o.client = :client AND o.date IN (:dates)')
                ->setParameter('dates', $dates)
                ->setParameter('client', $invoice->getClient())
                ->getResult();

            // reopen the rest
            $n += $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o '
                    . 'SET o.closed = 0 '
                    . 'WHERE o.client = :client AND o.date IN (:dates)')
                ->setParameter('client', $invoice->getClient())
                ->setParameter('dates', $dates)
                ->execute();

            // close all 110
            $n += $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o '
                    . 'SET o.closed = 1 '
                    . 'WHERE o.client = :client AND o.id IN (:orders)')
                ->setParameter('client', $invoice->getClient())
                ->setParameter('orders', $cancelled_days)
                ->execute();
        }
    }
}