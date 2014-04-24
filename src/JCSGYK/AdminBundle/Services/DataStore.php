<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JCSGYK\AdminBundle\Entity\Relation;
use JCSGYK\AdminBundle\Entity\Client;

/**
 * Service for Data Store
 */
class DataStore
{
    private $company;
    private $parameters;
    private $paramgroups;

    private $parameterList = [];
    private $groups = [];

    private $container;

    /** Map for client type slugs and ID-s */
    private $clientTypeMap = [
        Client::FH => 'fh',
        Client::CW => 'cw',
        Client::CA => 'ca'
    ];

    /** Map for client type names */
    private $clientTypeNames = [
        Client::FH => 'Családsegítő',
        Client::CW => 'Gyermekjólét',
        Client::CA => 'Étkeztetés'
    ];

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
            // decode json fields
            $json_fields = ['sequencePolicy', 'caseNumberTemplate'];
            foreach ($json_fields as $field) {
                $company[0][$field] = json_decode($company[0][$field], true);
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
     * Decide if a company has Child Welfare functions
     * @return boolean
     */
    public function companyIsCW()
    {
        $co = $this->getCompany();

        return false !== strpos($co['types'], (string) Client::CW);
    }

    /**
     * Decide if a company has Family Help functions
     * @return boolean
     */
    public function companyIsFH()
    {
        $co = $this->getCompany();

        return false !== strpos($co['types'], (string) Client::FH);
    }

    /**
     * Get the Paramgroup types
     * @return array
     */
    public function getGroupTypes()
    {
        return [
            0 => 'Rendszer',
            1 => 'Ügyfél',
            2 => 'Probléma',
            3 => 'Esemény'
        ];
    }

    /**
     * Returns all parametergroups in a doctrine object collection
     *
     * @return array $parametergroups
     */
    public function getParamgroups()
    {
        if (empty($this->paramgroups)) {
            $this->paramgroups = $this->container->get('doctrine')->getManager()
                ->getRepository('JCSGYKAdminBundle:Paramgroup')
                ->getAll($this->getCompanyId());
        }

        return $this->paramgroups;
    }

    /**
     * Returns parametergroup
     *
     * @return array $parametergroups
     */
    public function getParamgroupById($id)
    {
        $group = null;

        // pgroups with numeric id come from the database
        if (is_numeric($id)) {
            $groups = $this->getParamgroups();

            foreach ($groups as $g) {
                if ($g->getId() == $id) {
                    $group = $g;
                    break;
                }
            }
        }
        // pgroups with string ids (keys) come from Reources/config/parameters.yml
        else {
            $groups = $this->container->getParameter('system_parameter_groups');

            if (isset($groups[$id])) {
                $group = $groups[$id];
            }
        }

        return $group;
    }

    /**
     * Returns only the given type parametergroup, or all groups if no $type given
     * if unknown group id received, false will return
     *
     * @param integer $type
     * @param boolean $all return all, or only the active groups?
     * @return array
     */
    public function getParamGroup($type = null, $all = false)
    {
        $groups = $this->getParamgroups();

        $re = [];
        foreach ($groups as $grp) {
            if (($all || $grp->getIsActive()) && (is_null($type) || $grp->getType() == $type)) {
                $re[] = $grp;
            }
        }

        return $re;
    }

    /**
     * Get the available client types base on the company settings and user roles
     *
     * @return array
     */
    public function getClientTypes()
    {
        // $client_types = [
        //     Client::FH => 'Családsegítő',
        //     Client::CW => 'Gyermekjólét',
        //     Client::CA => 'Étkeztetés'
        // ];
        $client_types = $this->clientTypeNames;

        // check the company for enabled types, and remove the unneded ones
        $co = $this->getCompany();
        if (!empty($co['types'])) {
            $client_types = array_intersect_key($client_types, array_flip(explode(',', $co['types'])));
        }
        $sec = $this->container->get('security.context');

        // the new client's type is depending of the users roles
        if (!$sec->isGranted('ROLE_SUPER_ADMIN') && !$sec->isGranted('ROLE_ASSISTANCE')) {
            if (!$sec->isGranted('ROLE_FAMILY_HELP')) {
                unset($client_types[Client::FH]);
            }
            if (!$sec->isGranted('ROLE_CHILD_WELFARE')) {
                unset($client_types[Client::CW]);
            }
            if (!$sec->isGranted('ROLE_CATERING')) {
                unset($client_types[Client::CA]);
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
     * Returns the selected parameter name or the input if no param found
     *
     * @param int or array $ids paramter id or array of ids
     * @param int $group optional ParamterGroup id
     */
    public function get($ids = null, $group = null)
    {
        if (is_null($group)) {
            $params = $this->getList();
        }
        else {
            $params = $this->getGroup($group);
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $re = [];
        foreach ($ids as $id) {
            $re[] = isset($params[$id]) ? $params[$id] : $id;
        }

        return implode(', ', $re);
    }

    /**
     * Returns the selected group, or all groups and parameters if no $id given
     * if unknown group id received, false will return
     *
     * @param mixed - int $id (in case of paramgroups) or string $key for named system params
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

    /**
     * Converts parameters to the right type according to $is_multiple
     *
     * Multiple chioce widgets need arrays as input data, single chioce widgets need scalars
     * @param mixed $param
     * @param bool $is_multiple
     * @return mixed converted parameter
     */
    public function paramConvert($param, $is_multiple = false)
    {
        if ($is_multiple) {
            if (!is_array($param)) {
                $param = [$param];
            }
        } else {
            if (is_array($param)) {
                $param = reset($param);
            }
        }

        return $param;
    }

    /**
     * Return client type map
     *
     * @return array
     */
    public function getAllClientTypes()
    {
        return $this->clientTypeMap;
    }

    /**
     * Return client type names
     *
     * @return array
     */
    public function getClientTypeNames()
    {
        return $this->clientTypeNames;
    }

    /**
     * Return page slug from constant of client's type
     * Or the client type map if called without a parameter
     *
     * @return mixed
     */
    public function getSlugFromClientType($client_type = null)
    {
        if (is_null($client_type)) {
            return $this->clientTypeMap;
        }

        return isset($this->clientTypeMap[$client_type]) ? $this->clientTypeMap[$client_type] : false;
    }

    /**
     * Return client_type from the page slug
     * @return mixed
     */
    public function getClientTypeFromSlug($slug)
    {
        $map = array_flip($this->clientTypeMap);

        return isset($map[$slug]) ? $map[$slug] : false;
    }

}