<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ParameterRepository
 */
class ClientRepository extends EntityRepository
{
    /**
     * Find undeleted Events ordered by event date
     */
    public function getProblemList($client_id, $order = 'DESC')
    {
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        return $this->getEntityManager()
            ->createQuery("SELECT p FROM JCSGYKAdminBundle:Problem p WHERE p.client=:client AND p.isDeleted=0 ORDER BY p.createdAt {$order}")
            ->setParameter('client', $client_id)
            ->getResult();
    }

    /**
     * Find the parents of a client
     */
    public function getRelations($client_id, $type = null)
    {
        if (!is_null($type)) {
            return $this->getEntityManager()
                ->createQuery("SELECT c FROM JCSGYKAdminBundle:Relation c WHERE c.childId=:client AND c.type=:type ORDER BY c.type")
                ->setParameter('client', $client_id)
                ->setParameter('type', $type)
                ->getResult();
        }
        else {
            return $this->getEntityManager()
                ->createQuery("SELECT c FROM JCSGYKAdminBundle:Relation c WHERE c.childId=:client ORDER BY c.type")
                ->setParameter('client', $client_id)
                ->getResult();
        }
    }
}