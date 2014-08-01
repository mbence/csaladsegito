<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Entity\Task;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * TaskRepository
 */
class TaskRepository extends EntityRepository
{
    /**
     * Close all related task for the given problem with the given type
     *
     * @param Problem $problem
     * @param int $type
     */
    public function closeAll(Problem $problem, $type)
    {
        return $this->getEntityManager()
            ->createQuery("UPDATE JCSGYKAdminBundle:Task t SET t.status=:status WHERE t.problem=:problem AND t.type=:type AND t.status!=:status")
            ->setParameter('problem', $problem)
            ->setParameter('type', $type)
            ->setParameter('status', Task::STATUS_DONE)
            ->getResult();
    }

    /**
     * Return the active tasks
     * @param int $type
     * @param \Symfony\Component\Security\Core\SecurityContext $sec
     */
    public function getList($type, SecurityContext $sec)
    {
        $user = $sec->getToken()->getUser();

        if ($type == Task::TYPE_VISIT || $type == Task::TYPE_DISPATCH) {
            return $this->getEntityManager()
                ->createQuery("SELECT t, c FROM JCSGYKAdminBundle:Task t LEFT JOIN t.client c WHERE t.type=:type AND t.assignee=:user AND t.status!=:status ORDER BY t.createdAt DESC")
                ->setParameter('type', $type)
                ->setParameter('user', $user)
                ->setParameter('status', Task::STATUS_DONE)
                ->getResult();
        }
        elseif ($type == Task::TYPE_CLOSE) {
            if (!$sec->isGranted('ROLE_ADMIN')) {
                // non admins see only their own closed-problem tasks
                return $this->getEntityManager()
                    ->createQuery("SELECT t, c, p, u, a FROM JCSGYKAdminBundle:Task t LEFT JOIN t.client c LEFT JOIN t.problem p LEFT JOIN t.assignee u LEFT JOIN c.catering a WHERE t.type=:type AND t.creator=:user AND t.status!=:status ORDER BY t.createdAt DESC")
                    ->setParameter('type', $type)
                    ->setParameter('user', $user)
                    ->setParameter('status', Task::STATUS_DONE)
                    ->getResult();
            }
            else {
                // admins see all closed-problem tasks that are their own or have no assignee yet
                return $this->getEntityManager()
                    ->createQuery("SELECT t, c, p, u, a FROM JCSGYKAdminBundle:Task t LEFT JOIN t.client c LEFT JOIN t.problem p LEFT JOIN t.assignee u LEFT JOIN c.catering a WHERE t.type=:type AND (t.assignee IS NULL OR t.assignee=:user) AND t.status!=:status ORDER BY t.createdAt DESC")
                    ->setParameter('type', $type)
                    ->setParameter('user', $user)
                    ->setParameter('status', Task::STATUS_DONE)
                    ->getResult();
            }
        }
    }
}