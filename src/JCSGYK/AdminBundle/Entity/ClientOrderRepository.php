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
        return $this->getEntityManager()
            ->createQuery("SELECT o FROM JCSGYKAdminBundle:ClientOrder o WHERE o.client = :client_id AND o.date >= :start_date AND o.date <= :end_date ORDER BY o.date, o.createdAt")
            ->setParameter('client_id', $client_id)
            ->setParameter('start_date', $start_date)
            ->setParameter('end_date', $end_date)
            ->getResult();
    }
}