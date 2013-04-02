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
    public function getEventList($problem_id)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT e FROM JCSGYKAdminBundle:Event e WHERE e.problem=:problem AND e.isDeleted=0 ORDER BY e.eventDate DESC, e.createdAt DESC')
            ->setParameter('problem', $problem_id)
            ->getResult();
    }
}