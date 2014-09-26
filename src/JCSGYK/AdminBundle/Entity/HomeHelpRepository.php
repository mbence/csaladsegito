<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use JCSGYK\AdminBundle\Entity\Invoice;

/**
 * ClientOrderRepository
 */
class HomeHelpRepository extends EntityRepository
{

    /**
     * Returns the clients of the selected Social Worker
     * @param int $social_worker id
     * @param int $company_id
     * @return array
     */
    public function getClientsBySocialWorker($social_worker, $company_id)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT c, h FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h WHERE h.socialWorker = :sw AND c.companyId = :co ORDER BY c.lastname, c.firstname")
            ->setParameter('sw', $social_worker)
            ->setParameter('co', $company_id)
            ->getResult();
    }
}