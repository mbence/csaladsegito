<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;
use JCSGYK\AdminBundle\Entity\DocTemplate;

class DocTemplateRepository extends EntityRepository
{
    /**
     * Find all templates and return in a flat $id => $name list
     * @param int $company_id
     * @param bool $client_template client type templates
     * @param bool $problem_template problem type templates
     * @return array
     */
    public function getTemplateList($company_id, $client_template = false, $problem_template = true, $club = null)
    {
        $club_filter = !empty($club) ? ' AND (t.club = :club OR t.club IS NULL) ' : ' AND t.club IS NULL ';

        $dql = "SELECT t FROM JCSGYKAdminBundle:DocTemplate t WHERE "
                . "t.companyId=:company AND t.isActive=1 "
                . "AND (t.clientTemplate=:client_template OR t.problemTemplate=:problem_template) "
                . "AND t.file IS NOT NULL "
                . $club_filter
                . "ORDER BY t.name ASC";

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('company', $company_id)
            ->setParameter('client_template', $client_template)
            ->setParameter('problem_template', $problem_template)
        ;
        if (!empty($club)) {
            $query->setParameter('club', $club);
        }

        $templates = $query->getResult();

        $re = [];
        foreach ($templates as $template) {
            $re[$template->getId()] = $template->getName();
        }

        return $re;
    }


}