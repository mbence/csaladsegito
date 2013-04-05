<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ParameterRepository
 */
class ProblemRepository extends EntityRepository
{
    /**
     * Find undeleted Events ordered by event date
     */
    public function getEventList($problem_id, $order = 'DESC')
    {
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }
        
        return $this->getEntityManager()
            ->createQuery("SELECT e FROM JCSGYKAdminBundle:Event e WHERE e.problem=:problem AND e.isDeleted=0 ORDER BY e.eventDate {$order}, e.createdAt {$order}")
            ->setParameter('problem', $problem_id)
            ->getResult();
    }
}