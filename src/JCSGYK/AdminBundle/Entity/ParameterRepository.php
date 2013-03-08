<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ParameterRepository
 */
class ParameterRepository extends EntityRepository
{
    /**
     * Find all Parameters, ordered by group, position and name
     */
    public function getAll($company = 1)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Parameter p WHERE p.companyId=:company ORDER BY p.group, p.position, p.name ASC')
            ->setParameter('company', $company)
            ->getResult();
    }
}