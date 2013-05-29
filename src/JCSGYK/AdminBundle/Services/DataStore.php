<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JCSGYK\AdminBundle\Entity\Relation;

/**
 * Service for Data Store
 */
class DataStore
{
    private $company;
    private $parameters;

    private $parameterList = [];
    private $groups = [];

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getCompany()
    {
        if (empty($this->company)) {
            $em = $this->container->get('doctrine')->getManager();
            $req = $this->container->get('request');
            // find the current company based on server host
            $company = $em->createQuery('SELECT c FROM JCSGYKAdminBundle:Company c WHERE c.host LIKE :host')
                ->setMaxResults(1)
                ->setParameter('host', '%' . $req->getHost() . '%')
                ->getArrayResult();

            // exception for unknown host
            if (empty($company)) {
                throw new HttpException(500, "Unknown host:" . $req->getHost());
            }
            $this->company = $company[0];
        }

        return $this->company;
    }

    public function getCompanyId()
    {
        $co = $this->getCompany();

        return isset($co['id']) ? $co['id'] : 1;
    }

    /**
     * Get the available client types base on the company settings and user roles
     *
     * @return array
     */
    public function getClientTypes()
    {
        $client_types = [
            1 => 'Családsegítő',
            2 => 'Gyermekjólét'
        ];
        // check the company for enabled types, and remove the unneded ones
        $co = $this->getCompany();
        if (!empty($co['types'])) {
            $client_types = array_intersect_key($client_types, array_flip(explode(',', $co['types'])));
        }
        $sec = $this->container->get('security.context');

        // the new client's type is depending of the users roles
        if (!$sec->isGranted('ROLE_SUPER_ADMIN') && !$sec->isGranted('ROLE_ASSISTANCE')) {
            if (!$sec->isGranted('ROLE_FAMILY_HELP')) {
                unset($client_types[1]);
            }
            if (!$sec->isGranted('ROLE_CHILD_WELFARE')) {
                unset($client_types[2]);
            }
        }

        return $client_types;
    }

    /**
     * Returns all parameters in a doctrine object collection
     *
     * @return array $parameters
     */
    public function getAll()
    {
        if (empty($this->parameters)) {
            $this->parameters = $this->container->get('doctrine')->getManager()
                ->getRepository('JCSGYKAdminBundle:Parameter')
                ->getAll($this->getCompanyId());
        }

        return $this->parameters;
    }

    /**
     * Returns all parameters in a flat $id => $param list
     *
     * @return array $parameterList
     */
    public function getList()
    {
        if (empty($this->parameterList)) {
            $this->parameterList = [];
            $params = $this->getAll();
            foreach ($params as $para) {
                $this->parameterList[$para->getId()] = $para->getName();
            }
        }

        return $this->parameterList;
    }

    /**
     * Returns the selected parameter or false, on faliure
     *
     * @param integer $id
     */
    public function get($id = null)
    {
        $params = $this->getList();

        return isset($params[$id]) ? $params[$id] : false;
    }

    /**
     * Returns the selected group, or all groups and parameters if no $id given
     * if unknown group id received, false will return
     *
     * @param integer $id
     * @param boolean $all return all, or only the active params?
     * @return mixed
     */
    public function getGroup($id = null, $all = false)
    {
        if (empty($this->groups)) {
            $this->groups = [];
            $params = $this->getAll();

            foreach ($params as $para) {
                if ($all || $para->getIsActive()) {
                    if (empty($this->groups[$para->getGroup()])) {
                        $this->groups[$para->getGroup()] = [];
                    }
                    $this->groups[$para->getGroup()][$para->getId()] = $para->getName();
                }
            }
        }

        return is_null($id) ? $this->groups : (isset($this->groups[$id]) ? $this->groups[$id] : false);
    }

    /**
     * Return the month array, or a single month
     * @param type $month
     * @return type
     */
    public function getMonth($month = 0)
    {
        $months = ['január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december'];

        return empty($month) || !isset($months[$month - 1]) ? $months : $months[$month - 1];
    }

    /**
     * Return the parent types
     * @return array
     */
    public function getRelationTypes()
    {
        return [
            Relation::MOTHER => 'Anya',
            Relation::FATHER => 'Apa',
            Relation::GUARDIAN => 'Gyám'
        ];
    }
}