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
    public function getProblemList($client_id)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Problem p WHERE p.client=:client AND p.isDeleted=0 ORDER BY p.createdAt DESC')
            ->setParameter('client', $client_id)
            ->getResult();
    }
}