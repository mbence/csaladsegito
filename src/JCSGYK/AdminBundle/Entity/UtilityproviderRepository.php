<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UtilityproviderRepository
 *
 */
class UtilityproviderRepository extends EntityRepository
{
    public function getActive()
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Utilityprovider p WHERE p.isActive=1 ORDER BY p.name ASC');

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}