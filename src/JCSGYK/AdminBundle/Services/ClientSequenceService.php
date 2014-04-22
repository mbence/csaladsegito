<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Stat;

use JCSGYK\AdminBundle\Entity\ClientSequence;
use JCSGYK\AdminBundle\Entity\Company;

/**
 * ClientSequence Service
 */
class ClientSequenceService
{
    /** Entity manager */
    private $em;

    /** Constructor */
    public function __construct($doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    /**
     * Resets or creates the sequence for a given company
     * @param int $company_id
     * @param int $start optional starting sequence
     */
    public function reset($company, $type, $start = 0)
    {
        $this->em->createQuery("DELETE FROM JCSGYKAdminBundle:ClientSequence s WHERE s.companyId=:company AND s.type=:type")
            ->setParameter('company', $company['id'])
            ->setParameter('type', $type)
            ->getResult();

        $seq = new ClientSequence;
        $seq->setId($start);
        $seq->setCompanyId($company['id']);
        $seq->setType($type);

        if ($company['sequencePolicy'][$type] == Company::BY_YEAR) {
            $seq->setYear(date('Y'));
        }
        $this->em->persist($seq);
        $this->em->flush();
    }

    public function setYear($company, $type, $year)
    {
        $db = $this->em->getConnection();

        if (empty($year)) {
            $year = 0;
        }

        $sql = "UPDATE client_sequence SET year=:year WHERE company_id=:company AND type=:type";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('year', $year);
        $stmt->bindValue('company', $company['id']);
        $stmt->bindValue('type', $type);
        $stmt->execute();
    }

    /**
     * Return the next case number id in the client table
     * When no result, we reset the sequence to create the rewcord
     * @param int $company_id
     * @return array
     */
    public function nextVal($company, $type)
    {
        $res = $this->getNext($company, $type);

         // in case of a missing sequence, or when there is a year change we reset / create it
        if (false === $res || ($company['sequencePolicy'][$type] == Company::BY_YEAR && $res['year'] != date('Y'))) {
            $this->reset($company, $type);
            $res = $this->getNext($company, $type);
        }

        return $res;
    }

    /**
     * Return the next case number id in the client table
     * @param int $company
     *
     * @return array
     */
    private function getNext($company, $type)
    {
        // native db calls for LAST_INSERT_ID
        $db = $this->em->getConnection();

        $sql = "UPDATE client_sequence SET id=LAST_INSERT_ID(id+1) WHERE company_id=:company AND type=:type";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('company', $company['id']);
        $stmt->bindValue('type', $type);
        $stmt->execute();

        $sql = "SELECT LAST_INSERT_ID() as id, year FROM client_sequence WHERE company_id=:company AND type=:type";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('company', $company['id']);
        $stmt->bindValue('type', $type);
        $stmt->execute();

        $res = $stmt->fetchAll();

        return !empty($res[0]) ? $res[0] : false;
    }



}