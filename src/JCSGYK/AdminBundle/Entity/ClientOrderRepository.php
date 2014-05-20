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

    public function updateMenu($client_id, $start_date, $new_menu)
    {
        return $this->getEntityManager()->createQuery('UPDATE JCSGYKAdminBundle:ClientOrder o SET o.menu = :menu WHERE o.client = :client_id AND o.date >= :start_date')
            ->setParameter('client_id', $client_id)
            ->setParameter('start_date', $start_date)
            ->setParameter('menu', $new_menu)
            ->execute();
    }
}