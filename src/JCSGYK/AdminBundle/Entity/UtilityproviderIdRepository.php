<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UtilityproviderIdRepository
 *
 */
class UtilityproviderIdRepository extends EntityRepository
{
    public function findProviders($id)
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT i, p FROM JCSGYKAdminBundle:UtilityproviderId i
                JOIN i.utilityprovider p
                WHERE i.person = :id'
            )->setParameter('id', $id);

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}