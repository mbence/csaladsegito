<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use JCSGYK\AdminBundle\Entity\Client;

/**
 * ClientOrderRepository
 */
class HomeHelpRepository extends EntityRepository
{

    /**
     * Returns the clients of the selected Social Worker
     * @param int $social_worker id
     * @param int $company_id
     * @param bool $active
     * @return Client[]
     */
    public function getClientsBySocialWorker($social_worker, $company_id, $active = true, $only_ids = false)
    {
        $selector = $only_ids ? 'c.id': 'c, h';

        $result = $this->getEntityManager()
            ->createQuery("SELECT {$selector} FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h WHERE h.socialWorker = :sw AND c.companyId = :co AND h.isActive = :active ORDER BY c.lastname, c.firstname")
            ->setParameter('sw', $social_worker)
            ->setParameter('co', $company_id)
            ->setParameter('active', $active)
            ->getResult();

        // convert the results to a 1 dimension array
        if ($only_ids) {
            $result = array_map('current', $result);
        }

        return $result;
    }

    /**
     * Returns the clients with active home help
     * @param int $company_id
     * @return Client[]
     */
    public function getActiveClients($company_id)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT c, h FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h WHERE c.companyId = :co AND h.isActive = 1 ORDER BY c.lastname, c.firstname")
            ->setParameter('co', $company_id)
            ->getResult();
    }


}