<?php

namespace JCSGYK\AdminBundle\Services;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\UnitOfWork;

class HistoryListener
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $logger = $this->container->get('history.logger');
        $em   = $eventArgs->getEntityManager();
        $uow  = $em->getUnitOfWork();
        $ds = $this->container->get('jcs.ds');

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $action = 'insert';
            // get entity info
            $info = $ds->getHistoryInfo($entity, $action, $uow);

            if (!empty($info['hash'])) {
                $logger->queue($entity, $action, null, $info['data']);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $action = 'update';
            // get entity info
            $info = $ds->getHistoryInfo($entity, $action, $uow);
            if (!empty($info['hash'])) {
                $changeset = $uow->getEntityChangeSet($entity);

                $changeset = $this->filterChanges($entity, $changeset);

                if (!empty($changeset)) {
                    // check for soft delete events
                    if (!empty($changeset['isDeleted'])) {
                        $action = 'delete';
                        // refresh the info
                        $del_info = $ds->getHistoryInfo($entity, $action, $uow);
                        // add an extra log for the parent
                        $logger->queue($entity, $action, $del_info['hash'], $del_info['data']);
                    }

                    // add the info data to the changeset
                    if (!empty($info['data'])) {
                        $changeset = [$info['data'] => []] + $changeset;
                    }

                    $logger->queue($entity, $action, $info['hash'], $changeset);
                }
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $action = 'delete';
            // get entity info
            $info = $ds->getHistoryInfo($entity, $action, $uow);

            if (!empty($info['hash'])) {
                $logger->queue($entity, $action, $info['hash'], $info['data']);
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $col) {

        }

        foreach ($uow->getScheduledCollectionUpdates() as $col) {

        }
    }
/*
    private function addLog($hash, $event, $data, $em = null, $uow = null)
    {
        $logger = $this->container->get('history.logger');
        $log = $logger->log($hash, $event, $data);
        var_dump($log->getId());

        if (!is_null($em) && !is_null($uow)) {
            $classMetadata = $em->getClassMetadata(get_class($log));
            $uow->computeChangeSet($classMetadata, $log);
        }
    }
*/
    /**
     * Removes unchanged or ignore fields from the changeset
     *
     * @param array $changeset
     * @param array $fields
     * @return array
     */
    private function filterChanges($entity, $changeset)
    {
        $re = [];
        $fields = [];
        if (method_exists($entity, 'getHistoryFields')) {
            $fields = $entity->getHistoryFields();
        }

        if (is_array($changeset)) {
            foreach ($changeset as $field => $versions) {
                // check the entity history info
                if (!empty($fields) && is_array($fields) && in_array($field, $fields)
                        // check for changes without type checking
                        && $versions[0] != $versions[1]) {

                    $re[$field] = $this->convert($versions);
                }
            }
        }

        return $re;
    }

    /**
     * Convert changeset arrays to readable log text
     *
     * @param array $versions
     * @return string
     */
    private function convert($versions)
    {
        $re = [];
        foreach ($versions as $k => $ver) {
            if (is_null($ver)) {
                $re[$k] = '';
            }
            elseif (false === $ver) {
                $re[$k] = 0;
            }
            elseif (empty($ver)) {
                $re[$k] = $ver;
            }
            elseif (is_object($ver)) {
                if ($ver instanceof \DateTime) {
                    // store only the date part if no time is set
                    $dtpl = '00:00:00' == $ver->format('H:i:s') ? 'Y-m-d' : 'Y-m-d H:i:s';
                    $re[$k] = $ver->format($dtpl);
                }
                elseif (method_exists($ver, '__toString')) {
                    $re[$k] = (string) $ver;
                }
                elseif (method_exists($ver, 'getId')) {
                    $re[$k] = $ver->getId();
                }
            }
            elseif (is_array($ver)) {
                $re[$k] = json_encode($ver);
            }
            else {

                $re[$k] = (string) $ver;
            }
        }

        return $re;
    }
}
