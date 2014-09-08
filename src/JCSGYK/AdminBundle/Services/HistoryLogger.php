<?php

namespace JCSGYK\AdminBundle\Services;

use JCSGYK\AdminBundle\Entity\History;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HistoryLogger
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function log($hash, $event, $data)
    {
        $em = $this->container->get('doctrine')->getManager();
        $ds = $this->container->get('jcs.ds');
        $user = $this->container->get('security.context')->getToken()->getUser();

        $log = new History();
        $log->setCompanyId($ds->getCompanyId());
        $log->setHash($hash);
        $log->setEvent($event);
        $log->setData($data);

        if (!empty($user)) {
            $log->setUser($user);
        }

        $em->persist($log);

        return $log;
    }

    /**
     * Read the history entries of an entity
     * @param object $entity
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getLogs($entity, $offset = 0, $limit = 10)
    {
        $re = [];
        if (method_exists($entity, 'getHistoryInfo')) {
            $info = $entity->getHistoryInfo();

            if (!empty($info['parent'])) {
                $em = $this->container->get('doctrine')->getManager();
                $ds = $this->container->get('jcs.ds');
                
                $re = $em->createQuery("SELECT h, u FROM JCSGYKAdminBundle:History h JOIN h.user u WHERE h.companyId = :company_id AND h.hash = :hash ORDER BY h.createdAt DESC")
                ->setParameter('company_id', $ds->getCompanyId())
                ->setParameter('hash', $info['parent'])
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getResult();
            }
        }

        return $re;
    }


    /**
     * Returns the class name without the namespace
     * @param object $entity
     * @return string
     */
    private function getClassName($entity)
    {
        return join('', array_slice(explode('\\', get_class($entity)), -1));
    }
}
