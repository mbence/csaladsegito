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
     * Finds the changes for the client
     * @param int $client_id
     * @param DateTime $end_date end of the invoice period
     * @param int $status optional
     * @return array of JCSGYK\AdminBundle\Entity\ClientOrder
     */
    public function getChanges($client_id, $end_date, $status = ClientOrder::OPEN)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT o FROM JCSGYKAdminBundle:ClientOrder o WHERE o.client = :client_id AND o.date < :end_date AND o.status = :status ORDER BY o.date, o.createdAt")
            ->setParameter('client_id', $client_id)
            ->setParameter('end_date', $end_date)
            ->setParameter('status', $status)
            ->getResult();
    }
}