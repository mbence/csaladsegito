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
    public function getAll($company_id)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Paramgroup p WHERE p.companyId=0 OR p.companyId=:company ORDER BY p.clientType, p.type, p.position, p.name ASC')
            ->setParameter('company', $company_id)
            ->getResult();
    }

    /**
     * Get one Paramgroup entity
     * @param int $id
     * @param int $company_id
     * @return Paramgroup
     */
    public function getOne($id, $company_id)
    {
        $query = $this->getEntityManager()
            ->createQuery('SELECT p FROM JCSGYKAdminBundle:Paramgroup p WHERE p.id=:id AND (p.companyId=:company OR p.companyId=0)')
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