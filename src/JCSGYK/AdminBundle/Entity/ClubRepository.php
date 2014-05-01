<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use JCSGYK\AdminBundle\Entity\Client;

/**
 * ClubRepository
 */
class ClubRepository extends EntityRepository
{
    /**
     * Find all active Clubs, ordered name
     */
    public function getAll($company_id)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM JCSGYKAdminBundle:Club c WHERE c.companyId=:company_id AND c.isActive=1 ORDER BY c.name ASC')
            ->setParameter('company_id', $company_id)
            ->getResult();
    }
}