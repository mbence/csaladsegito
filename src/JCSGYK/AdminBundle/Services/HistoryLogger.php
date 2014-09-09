<?php

namespace JCSGYK\AdminBundle\Services;

use JCSGYK\AdminBundle\Entity\History;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HistoryLogger
{

    private $container;
    private $logQueue = [];

    private $on = true;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function on()
    {
        $this->on = true;
    }

    public function off()
    {
        $this->on = false;
    }

    public function log($hash, $event, $data)
    {
        $em   = $this->container->get('doctrine')->getManager();
        $ds   = $this->container->get('jcs.ds');
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
        $ds = $this->container->get('jcs.ds');
        $info = $ds->getHistoryInfo($entity);

        if (!empty($info['hash'])) {
            $em = $this->container->get('doctrine')->getManager();

            $re = $em->createQuery("SELECT h, u FROM JCSGYKAdminBundle:History h JOIN h.user u WHERE h.companyId = :company_id AND h.hash = :hash ORDER BY h.createdAt DESC")
            ->setParameter('company_id', $ds->getCompanyId())
            ->setParameter('hash', $info['hash'])
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getResult();
        }

        return $re;
    }

    /**
     * Adds an element to the log queue
     * @param object $entity
     * @param string $action (insert, update or delete)
     */
    public function queue($entity, $action, $hash = null, $data = null)
    {
        if ($this->on) {
            $this->logQueue[] = [$entity, $action, $hash, $data];
        }
    }

    public function processQueue()
    {
        $ds = $this->container->get('jcs.ds');
        $recNum = 0;

        while (!empty($this->logQueue)) {
            list($entity, $action, $hash, $data) = array_shift($this->logQueue);
            if (empty($hash)) {
                $hash = $this->getHash($entity, $action);
                // still no hash, we skip this entry
                if (empty($hash)) {
                    continue;
                }
            }
//            if (empty($data)) {
//                $data = $entity->getId();
//            }
            $event = sprintf('%s %s', $ds->getClassName($entity), $action);

            $this->log($hash, $event, $data);
            $recNum++;
        }

        if ($recNum > 0) {
            $em = $this->container->get('doctrine')->getManager();
            $em->flush();
        }
    }

    private function getHash($entity, $action)
    {
        $ds = $this->container->get('jcs.ds');
        $info = $ds->getHistoryInfo($entity, $action);
        if (!empty($info['hash'])) {

            return $info['hash'];
        }
    }
}
