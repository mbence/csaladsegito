<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

class TemplateRepository extends EntityRepository
{
    /**
     * Find all templates and return in a flat $id => $name list
     */
    public function getTemplateList($company_id)
    {
        $templates = $this->getEntityManager()
            ->createQuery("SELECT t FROM JCSGYKAdminBundle:Template t WHERE t.companyId=:company AND t.isActive=1 AND t.path IS NOT NULL ORDER BY t.name ASC")
            ->setParameter('company', $company_id)
            ->getResult();

        $re = [];
        foreach ($templates as $template) {
            $re[$template->getId()] = $template->getName();
        }

        return $re;
    }
}