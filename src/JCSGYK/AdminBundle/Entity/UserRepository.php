<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 */
class UserRepository extends EntityRepository
{
    public function findActive()
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT u FROM JCSGYKAdminBundle:User u WHERE u.enabled = 1 ORDER BY u.lastname, u.firstname');
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function getUsers($company_id)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT u FROM JCSGYKAdminBundle:User u WHERE u.company_id = :company_id ORDER BY u.lastname, u.firstname')
            ->setParameter('company_id', $company_id)
            ->getResult();
    }
}