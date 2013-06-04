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
        //var_dump($this);

        $query = $this->getEntityManager()
            ->createQuery('SELECT u FROM JCSGYKAdminBundle:User u WHERE u.enabled = 1 ORDER BY u.lastname, u.firstname');
        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}