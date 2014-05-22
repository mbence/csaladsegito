<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

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
            ->createQuery("SELECT o FROM JCSGYKAdminBundle:ClientOrder o WHERE o.client = :client_id AND o.date <= :end_date AND o.closed = :closed ORDER BY o.date, o.createdAt")
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
            ->createQuery("SELECT o FROM JCSGYKAdminBundle:ClientOrder o WHERE o.client = :client_id AND o.date >= :start_date AND o.date <= :end_date ORDER BY o.date, o.createdAt")
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

        return $this->getEntityManager()
            ->createQuery("SELECT b.id, a.menu, COUNT(o) as orders "
                    . "FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c LEFT JOIN c.catering a JOIN a.club b "
                    . "WHERE o.companyId = :company_id AND o.order = 1 AND o.cancel = 0 "
                    . "AND o.date >= :date AND o.date <= :end_date "
                    . "GROUP BY a.club, a.menu")
            ->setParameter('company_id', $company_id)
            ->setParameter('date', $date)
            ->setParameter('end_date', $end_date)
            ->getResult();
    }
}