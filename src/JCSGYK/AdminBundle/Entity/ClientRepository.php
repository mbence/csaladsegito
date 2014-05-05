<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use JCSGYK\AdminBundle\Entity\Client;
use Doctrine\ORM\Query;
use JCSGYK\AdminBundle\Entity\User;

/**
 * ClientRepository
 */
class ClientRepository extends EntityRepository
{
    /**
     * Find undeleted Events ordered by event date
     */
    public function getProblemList($client_id, $order = 'DESC')
    {
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        return $this->getEntityManager()
            ->createQuery("SELECT p FROM JCSGYKAdminBundle:Problem p WHERE p.client=:client AND p.isDeleted=0 ORDER BY p.createdAt {$order}")
            ->setParameter('client', $client_id)
            ->getResult();
    }

    /**
     * Find the parents of a client
     */
    public function getRelations($client_id, $type = null)
    {
        if (!is_null($type)) {
            return $this->getEntityManager()
                ->createQuery("SELECT c FROM JCSGYKAdminBundle:Relation c WHERE c.childId=:client AND c.type=:type ORDER BY c.type")
                ->setParameter('client', $client_id)
                ->setParameter('type', $type)
                ->getResult();
        }
        else {
            return $this->getEntityManager()
                ->createQuery("SELECT c FROM JCSGYKAdminBundle:Relation c WHERE c.childId=:client ORDER BY c.type")
                ->setParameter('client', $client_id)
                ->getResult();
        }
    }

    /**
     * Find the children of a client
     */
    public function getChildren(Client $client)
    {
        return $this->getEntityManager()
                ->createQuery("SELECT c FROM JCSGYKAdminBundle:Relation c WHERE c.parent=:client")
                ->setParameter('client', $client)
                ->getResult();
    }

    /**
     * Find the records associated with a clients case
     */
    public function getCase(Client $client)
    {
        $year = $client->getCaseYear();
        $yop = is_null($year) ? 'IS NULL' : '=:year';

        $q = $this->getEntityManager()
            ->createQuery("SELECT c FROM JCSGYKAdminBundle:Client c WHERE c.companyId=:co AND c.caseYear {$yop} AND c.caseNumber=:num")
            ->setParameter('co', $client->getCompanyId())
            ->setParameter('num', $client->getCaseNumber());

        if (!is_null($year)) {
            $q->setParameter('year', $year);
        }

        return $q->getResult();
    }

    /**
     * Saves the parameters to client_param
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @param array $param_data ($paramgroup_id => $value)
     */
    public function saveParams(Client $client, $param_data)
    {
        $client_params = $client->getParams();
        foreach ($param_data as $group_id => $value) {
            $act = $client_params->current();
            if (empty($act)) {
                // create new params
                $act = new ClientParam();
                $act->setClient($client);
                $this->getEntityManager()->persist($act);
            }
            $act->setParamgroupId($group_id);
            $act->setValue($value);

            $client_params->next();
        }
    }

    /**
     * Find clients belonging to a case Admin
     */
    public function getClientsByCaseAdmin($company_id, $case_admin = false, $client_type = null)
    {
        $sql = 'SELECT c FROM JCSGYKAdminBundle:Client c WHERE c.companyId=:company_id';
        if (false !== $case_admin) {
            if (is_null($case_admin)) {
                $sql .= ' AND c.caseAdmin IS NULL';
            }
            else {
                $sql .= ' AND c.caseAdmin=:case_admin';
            }
        }
        if (!empty($client_type)) {
            $sql .= ' AND c.type=:client_type';
        }
        $sql .= ' ORDER BY c.caseLabel, c.lastname, c.firstname';

        $q = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('company_id', $company_id);

        if (!empty($case_admin)) {
            $q->setParameter('case_admin', $case_admin);
        }
        if (!empty($client_type)) {
            $q->setParameter('client_type', $client_type);
        }

        // stop loading the Catering records
//        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        return $q->getResult();
    }

    public function getCaseCounts($company_id, $case_admin = null, $client_type = null)
    {
        if ($case_admin instanceof User) {
            $case_admin = $case_admin->getId();
        }
        $where = ['company_id = :company_id'];
        $params['company_id'] = $company_id;

        if (!is_null($case_admin)) {
            $where[] = 'case_admin = :case_admin';
            $params['case_admin'] =$case_admin;
        }
        if (!is_null($client_type)) {
            $where[] = 'type = :client_type';
            $params['client_type'] =$client_type;
        }

        $where = implode(' AND ', $where);
        $sql = "SELECT case_admin, COUNT(*) AS total, COUNT(CASE WHEN is_archived = 0 THEN 1 END) AS active, COUNT(CASE WHEN is_archived = 1 THEN 1 END) AS archived FROM client WHERE {$where} GROUP BY case_admin";

        return $this->getEntityManager()->getConnection()->executeQuery($sql, $params)->fetchAll();
    }
/*
 *
 */
}