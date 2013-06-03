<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ParamgroupRepository
 */
class ParamgroupRepository extends EntityRepository
{
    /**
     * Find all Parameter groups, ordered by type, position and label
     */
    public function getAll($company = 1)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Paramgroup p WHERE p.companyId=:company ORDER BY p.type, p.position, p.label ASC')
            ->setParameter('company', $company)
            ->getResult();
    }
}