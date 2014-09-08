<?php

namespace JCSGYK\AdminBundle\Services;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

        foreach ($uow->getScheduledEntityInsertions() as $entity) {

            // check for entity info
            if (method_exists($entity, 'getHistoryInfo')) {
                $info = $entity->getHistoryInfo();

                $action = sprintf('%s insert', $info['class']);
                $log = $logger->log($info['parent'], $action, $entity->getId());

                $classMetadata = $em->getClassMetadata(get_class($log));
                $uow->computeChangeSet($classMetadata, $log);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {

            // check for entity info
            if (method_exists($entity, 'getHistoryInfo')) {
                $info = $entity->getHistoryInfo();
                $changeset = $uow->getEntityChangeSet($entity);

                $changeset = $this->filterChanges($changeset, $info);

                if (!empty($changeset)) {
                    $action = sprintf('%s update', $info['class']);
                    $log = $logger->log($info['parent'], $action, $changeset);

                    $classMetadata = $em->getClassMetadata(get_class($log));
                    $uow->computeChangeSet($classMetadata, $log);
                }
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            // check for entity info
            if (method_exists($entity, 'getHistoryInfo')) {
                $info = $entity->getHistoryInfo();

                $action = sprintf('%s delete', $this->getClassName($entity));
                $logger->log($info['parent'], $action, $entity->getId());
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $col) {

        }

        foreach ($uow->getScheduledCollectionUpdates() as $col) {

        }
    }

    /**
     * Removes unchanged or ignore fields from the changeset
     *
     * @param array $changeset
     * @return array
     */
    private function filterChanges($changeset, $info)
    {
        $re = [];
        if (is_array($changeset)) {
            foreach ($changeset as $field => $versions) {
                // check the entity history info
                if (!empty($info['fields']) && is_array($info['fields']) && in_array($field, $info['fields'])
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
            else {

                $re[$k] = (string) $ver;
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
