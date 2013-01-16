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
    public function getAll()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Parameter p ORDER BY p.group, p.position, p.name ASC')
            ->getResult();
    }

    /**
     * Get a group of parameters, ordered by position and name
     *
     * @param integer $group group id
     */
    public function getGroup($group)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Parameter p WHERE p.group=:group AND is_active=1 ORDER BY p.group, p.position, p.name ASC')
            ->setParameter('group', $group)
            ->getResult();
    }
}