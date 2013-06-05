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
    public function getAll($company_id)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Parameter p WHERE (p.companyId=0 OR p.companyId=:company) ORDER BY p.group, p.position, p.name ASC')
            ->setParameter('company', $company_id)
            ->getResult();
    }

    /**
     * Get one Parameter entity
     * @param int $id
     * @param int $company_id
     * @return Paramgroup
     */
    public function getOne($id, $company_id)
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Parameter p WHERE p.id=:id AND (p.companyId=0 OR p.companyId=:company)')
            ->setParameter('id', $id)
            ->setParameter('company', $company_id)
            ->setMaxResults(1);

        try {
            return $query->getSingleResult();
        }
        catch (\Exception $e) {
            return null;
        }
    }
}