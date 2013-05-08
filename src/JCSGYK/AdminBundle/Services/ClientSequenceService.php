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
     */
    public function reset($company)
    {
        $this->em->createQuery("DELETE FROM JCSGYKAdminBundle:ClientSequence s WHERE s.companyId=:company")
            ->setParameter('company', $company['id'])
            ->getResult();

        $seq = new ClientSequence;
        $seq->setId(0);
        $seq->setCompanyId($company['id']);
        if ($company['sequencePolicy'] == Company::BY_YEAR) {
            $seq->setYear(date('Y'));
        }
        $this->em->persist($seq);
        $this->em->flush();
    }

    public function setYear($company, $year)
    {
        $db = $this->em->getConnection();

        $sql = "UPDATE client_sequence SET year=:year WHERE company_id=:company";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('year', $year);
        $stmt->bindValue('company', $company['id']);
        $stmt->execute();
    }

    /**
     * Return the next case number id in the client table
     * When no result, we reset the sequence to create the rewcord
     * @param int $company_id
     * @return array
     */
    public function nextVal($company)
    {
        $res = $this->getNext($company);

         // in case of a missing sequence, or when there is a year change we reset / create it
        if (false === $res || ($company['sequencePolicy'] == Company::BY_YEAR && $res['year'] != date('Y'))) {
            $this->reset($company);
            $res = $this->getNext($company);
        }

        return $res;
    }

    /**
     * Return the next case number id in the client table
     * @param int $company
     *
     * @return array
     */
    private function getNext($company)
    {
        // native db calls for LAST_INSERT_ID
        $db = $this->em->getConnection();

        $sql = "UPDATE client_sequence SET id=LAST_INSERT_ID(id+1) WHERE company_id=:company";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('company', $company['id']);
        $stmt->execute();

        $sql = "SELECT LAST_INSERT_ID() as id, year FROM client_sequence WHERE company_id=:company";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('company', $company['id']);
        $stmt->execute();

        $res = $stmt->fetchAll();

        return !empty($res[0]) ? $res[0] : false;
    }



}