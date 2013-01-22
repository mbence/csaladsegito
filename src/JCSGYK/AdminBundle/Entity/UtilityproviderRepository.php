<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UtilityproviderRepository
 *
 */
class UtilityproviderRepository extends EntityRepository
{
    public function findProviders($id)
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT p FROM JCSGYKAdminBundle:Utilityprovider p WHERE p.client_id = :id'
            )->setParameter('id', $id);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}