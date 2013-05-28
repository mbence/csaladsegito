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
    public function getParentList($client_id)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT c FROM JCSGYKAdminBundle:ClientParent c WHERE c.childId=:client ORDER BY c.type")
            ->setParameter('client', $client_id)
            ->getResult();
    }


    /**
     * Get the parents
     * returns an array by types
     * @param type $client_id
     */
    public function getParents($client_id)
    {
        $parents = $this->getParentList($client_id);

        $re = [];
        foreach ($parents as $parent) {
            $re[$parent->getType()] = $parent->getParent();
        }

        return $re;
    }
}