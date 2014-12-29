<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\HomehelpMonth;

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
    public function getClientsBySocialWorker($social_worker, $company_id, $month = null, $active = true, $only_ids = false)
    {
        $selector = $only_ids ? 'c.id': 'c, h';
        if (is_null($month)) {
            $month = new \DateTime('first day of this month');
        } elseif (!$month instanceof \DateTime) {
            $month = new \DateTime($month);
        }
        $month_end = $month->format('Y-m-t');

        if ($active) {
            $act_dql = " AND (h.agreementFrom <= :end AND (h.agreementTo IS NULL OR h.agreementTo >= :start) "
                    . "AND (h.pausedFrom IS NULL OR (h.pausedTo IS NOT NULL AND h.pausedTo < :end) OR (h.pausedFrom > :start))) ";
        } else {
            $act_dql = " AND (h.agreementFrom > :end OR (h.agreementTo IS NOT NULL AND h.agreementTo < :start) OR (h.pausedFrom IS NOT NULL AND h.pausedFrom <= :start AND (h.pausedTo IS NULL OR h.pausedTo >= :end)) ";
        }
        $result = $this->getEntityManager()
            ->createQuery("SELECT {$selector} FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h WHERE h.socialWorker = :sw AND c.companyId = :co AND c.isArchived=0 {$act_dql} ORDER BY c.lastname, c.firstname")
            ->setParameter('sw', $social_worker)
            ->setParameter('co', $company_id)
            ->setParameter('start', $month)
            ->setParameter('end', $month_end)
            ->getResult();

        // convert the results to a 1 dimension array
        if ($only_ids) {
            $result = array_map('current', $result);
        }

        return $result;
    }

    /**
     * Returns the clients of the selected Club
     * @param int $club id
     * @param int $company_id
     * @param bool $active
     * @return Client[]
     */
    public function getClientsByClub($club, $company_id, $active = true, $only_ids = false)
    {
        $selector = $only_ids ? 'c.id': 'c, h';

        $result = $this->getEntityManager()
            ->createQuery("SELECT {$selector} FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h WHERE h.club = :club AND c.companyId = :co AND c.isArchived=0 AND h.isActive = :active ORDER BY c.lastname, c.firstname")
            ->setParameter('club', $club)
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
            ->createQuery("SELECT c, h FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h WHERE c.companyId = :co AND h.isActive = 1 AND c.isArchived=0 ORDER BY c.lastname, c.firstname")
            ->setParameter('co', $company_id)
            ->getResult();
    }

    /**
     * Get the open (or closed) months for a client in a given period
     * @param $client_id
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int $is_closed
     * @return HomehelpMonth[]
     */
    public function getClientMonths($client_id, \DateTime $start, \DateTime $end, $is_closed = 0)
    {
        $start = $start->setTime(0,0,0)->format('Y-m-d');
        $end = $end->setTime(0,0,0)->format('Y-m-d');

        return $this->getEntityManager()
            ->createQuery("SELECT c, h FROM JCSGYKAdminBundle:HomehelpmonthsClients c JOIN c.homehelpmonth h WHERE c.client = :client_id AND h.date >= :start AND h.date <= :end AND c.isClosed = :closed ORDER BY h.date")
            ->setParameter('client_id', $client_id)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('closed', $is_closed)
            ->getResult();
    }
}