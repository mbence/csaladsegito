<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use JCSGYK\AdminBundle\Entity\Relation;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\MonthlyClosing;

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
    private $clubs;

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

    /** Map for client types and security roles */
    private $roleMap = [
        Client::FH => 'ROLE_FAMILY_HELP',
        Client::CW => 'ROLE_CHILD_WELFARE',
        Client::CA => 'ROLE_CATERING'
    ];

    /** List of all security roles */
    private $roles = [
        'ROLE_ASSISTANCE' => 'Asszisztens',
        'ROLE_FAMILY_HELP' => 'Családsegítő',
        'ROLE_CHILD_WELFARE' => 'Gyermekvédelem',
        'ROLE_CATERING' => 'Étkeztetés',
        'ROLE_ADMIN' => 'Admin',
    ];

    /** Holidays type */
    private $holidayTypeMap = [
        1 => 'munkaszünet',
        2 => 'munkanap',
        3 => 'pihenőnap'
    ];

    private $closingStatuses = [
        MonthlyClosing::RUNNING     => 'Fut',
        MonthlyClosing::SUCCESS     => 'Siker',
        MonthlyClosing::ERROR       => 'Hiba',
    ];

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Checks if the current user has access to the specified client type
     * Throws an exception on error
     *
     * @param int $client_type
     */
    public function userRoleCheck($client_type) {
        $sec = $this->container->get('security.context');

        if (!isset($this->roleMap[$client_type])) {
            throw new HttpException(500, "Unknown client type:" . $client_type);
        }

        if (!$sec->isGranted($this->roleMap[$client_type])) {
            throw new AccessDeniedHttpException('Invalid client type');
        }
    }

    public function getRoleMap()
    {
        return $this->roleMap;
    }

    public function getClosingStatus($status)
    {
        return isset($this->closingStatuses[$status]) ? $this->closingStatuses[$status] : false;
    }

    public function getRoles()
    {
        return $this->roles;
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
            $json_fields = ['types', 'sequencePolicy', 'caseNumberTemplate'];
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
     * Decide if a company has give type enabled
     * @return boolean
     */
    public function companyHas($type)
    {
        $co = $this->getCompany();

        return false !== in_array($type, $co['types']);
    }

    /**
     * Decide if a company has Child Welfare functions
     * @return boolean
     */
    public function companyIsCW()
    {
        return $this->companyHas(Client::CW);
    }

    /**
     * Decide if a company has Family Help functions
     * @return boolean
     */
    public function companyIsFH()
    {
        return $this->companyHas(Client::FH);
    }

    /**
     * Get the Paramgroup types
     * @return array
     */
    public function getGroupTypes($sys = false)
    {
        return $sys ? [
            0 => 'Rendszer',
        ]
            :
        [
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
    public function getParamGroup($type = null, $all = false, $client_type = null)
    {
        //$this->container->get('logger')->notice('Client type: ' . $client_type);

        $groups = $this->getParamgroups();

        $re = [];
        foreach ($groups as $grp) {
            if (($all || $grp->getIsActive())
                    && (is_null($type) || $grp->getType() == $type)
                    && (is_null($client_type) || $grp->getClientType() == $client_type)
            ) {
                $re[] = $grp;
            }
        }

        return $re;
    }

    /**
     * Get the available client types based on the company settings and user roles
     *
     * @return array
     */
    public function getClientTypes()
    {
        $client_types = $this->clientTypeNames;
        // check the company for enabled types, and remove the unneded ones
        $co = $this->getCompany();
        if (!empty($co['types'])) {
            $client_types = array_intersect_key($client_types, array_flip($co['types']));
        }
        $sec = $this->container->get('security.context');

        // the new client's type is depending of the users roles
        if (!$sec->isGranted('ROLE_SUPER_ADMIN') && !$sec->isGranted('ROLE_ASSISTANCE')) {
            foreach ($this->roleMap as $type => $role) {
                if (!$sec->isGranted($role)) {
                    unset($client_types[$type]);
                }
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
     *
     * TODO: fix this, coz its ugly
     */
    public function getRelationTypes($client_type = null)
    {
        // different relations for different types
        if (Client::CW == $client_type) {
            return [
                Relation::MOTHER => 'Anya',
                Relation::FATHER => 'Apa',
                Relation::GUARDIAN => 'Gyám',
            ];
        }
        elseif (Client::CA == $client_type) {
            return [
                Relation::RELATIVE => 'Hozzátartozó',
                Relation::DOCTOR => 'Orvos',
            ];
        }
        else {
            return [
                Relation::MOTHER => 'Anya',
                Relation::FATHER => 'Apa',
                Relation::GUARDIAN => 'Gyám',
                Relation::RELATIVE => 'Hozzátartozó',
                Relation::DOCTOR => 'Orvos',
            ];
        }
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
     * Return client type names map
     *
     * @param bool $active_only return only the active client types, set in the company record
     * @return array
     */
    public function getClientTypeNames($active_only = false)
    {
        $cts = $this->clientTypeNames;

        if ($active_only) {
            $cts = $this->typeFilter($cts);
        }

        return $cts;
    }

    /**
     * Removes the inactive client types from the input array based on the company record
     *
     * @param array $in like $this->clientTypeNames or clientTypeMap
     * @return array
     */
    public function typeFilter(array $in)
    {
        $out = [];
        $co = $this->getCompany();
        $enabled_types = $co['types'];

        foreach ($in as $k => $v) {
            if (in_array($k, $enabled_types)) {
                $out[$k] = $v;
            }
        }
        return $out;
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

    public function getClubs()
    {
        if (empty($this->clubs)) {
            $this->clubs = $this->container->get('doctrine')->getManager()
                ->getRepository('JCSGYKAdminBundle:Club')
                ->getAll($this->getCompanyId());
        }
//
//        $re = [];
//        foreach ($this->clubs as $club) {
//            $re[$club->getId()] = $club->getName();
//        }
//
//        return $re;
        return $this->clubs;
    }

    /**
     * Returns a list of the active users of the company, optionally filtered by the client type (based on role)
     *
     * @param int $client_type
     * @return array
     */
    public function getCaseAdmins($client_type = null, $active = true)
    {
        $em = $this->container->get('doctrine')->getManager();
        $sec = $this->container->get('security.context');

        $sql = 'SELECT u FROM JCSGYKAdminBundle:User u WHERE u.companyId=:company_id';
        if ($active) {
            $sql .= ' AND u.enabled=1';
        }
        if (!empty($client_type) && !empty($this->roleMap[$client_type])) {
            $role = $this->roleMap[$client_type];
            $sql .= " AND (u.roles LIKE '%{$role}%' OR u.roles LIKE '%ROLE_ADMIN%')";
        }

        // only SUPER_ADMINs should be able to see SUPER_ADMINs
        if (!$sec->isGranted('ROLE_SUPER_ADMIN')) {
            $sql .= " AND u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'";
        }
        $sql .= ' ORDER BY u.lastname, u.firstname';

        $users = $em->createQuery($sql)
            ->setParameter('company_id', $this->getCompanyId())
            ->getResult();

        $re = [];
//
//    $roles = [
//        'ROLE_ASSISTANCE' => 'Asszisztens',
//        'ROLE_FAMILY_HELP' => 'Családsegítő',
//        'ROLE_CHILD_WELFARE' => 'Gyermekvédelem',
//        'ROLE_CATERING' => 'Étkeztetés',
//        'ROLE_ADMIN' => 'Admin',
//    ];

        // set the initial order of the user arrays
        foreach ($this->roles as $role) {
            $re[$role] = [];
        }

        foreach ($users as $user) {
            $user_roles = $user->getRoles();

            // admin users should only appear in the admin lists
            if (in_array('ROLE_ADMIN', $user_roles)) {
                $user_roles = ['ROLE_ADMIN'];
            }
            // if $client_type is set, we should display multi-roled users in that group
            if (!empty($client_type)) {
                if (isset($this->roleMap[$client_type]) && in_array($this->roleMap[$client_type], $user_roles)) {
                    $user_roles = [$this->roleMap[$client_type]];
                }
            }

            // add each user to the corresponding role group but only once
            foreach ($user_roles as $role) {
                if (isset($this->roles[$role])) {
                    $re[$this->roles[$role]][] = $user;
                }
                break;
            }
        }

        return $re;
    }

    /**
     * Return holiday day types
     * @return array
     */
    public function getHolidayTypeMap()
    {
        return $this->holidayTypeMap;
    }

    /**
     * Get days of multiple month
     * @param DateTime $date
     * @param int $month_number
     * @return array
     */
    public function getDaysOfMonths($date, $month_number=3)
    {
        // why is $date reference and not copy???
        $new_date = clone $date;
        $months   = [];

        for ($i = 1; $i <= $month_number; $i++) {
            $months[$new_date->format('Y-m')] = $this->getDaysOfMonth($new_date);
            $new_date->modify('first day of next month');
        }

        return $months;
    }

    /**
     * Get days of one month
     * @param DateTime $date
     * @return array
     */
    public function getDaysOfMonth($date)
    {
        $month     = [];
        $week      = $date->format('W');
        $day_count = $date->format('t');
        $first_day = $date->format('N');
        $today     = date('j');
        $new_date  = clone $date;
        $new_date->modify('+' . ($day_count-1) . ' days');
        $last_day  = $new_date->format('N');

        for ($i = 1; $i < $first_day; $i++) {
            $month[] = [
                'day'     => null,
                'week'    => $week,
                'weekend' => ($i > 5) ? true : false
            ];
        }
        for ($i = 1; $i <= $day_count; $i++) {
            if (count($month) % 7 == 0) {
                $week++;
            }
            $month[] = [
                'day'     => $i,
                'week'    => $week,
                'weekend' => (count($month) % 7 == 5 || count($month) % 7 == 6) ? true : false,
                'modifiable' => ($i < ($today+2)) ? false : true
            ];
        }
        for ($i = 7; $i > $last_day; $i--) {
            $month[] = [
                'day'     => null,
                'week'    => $week,
                'weekend' => ($i > 5) ? true : false
            ];
        }

        return $month;
    }

    /**
     * Finds the actual option record for a given name
     *
     * @param string $name
     * @param string $date in ISO format
     * @return array of values or empty array on failure
     */
    public function getOption($name, $date = null) {
        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        $em = $this->container->get('doctrine')->getManager();
        $company_id = $this->getCompanyId();

        $table = $em->createQuery("SELECT o FROM JCSGYKAdminBundle:Option o WHERE o.companyId = :company_id AND o.name = :name AND o.isActive = 1 AND o.validFrom < :now ORDER BY o.validFrom DESC")
            ->setParameter('company_id', $this->getCompanyId())
            ->setParameter('name', $name)
            ->setParameter('now', $date)
            ->setMaxResults(1)
            ->getResult();

        $re = [];

        if (!empty($table[0])) {
            $re = json_decode($table[0]->getValue(), true);
        }

        return $re;
    }

    /**
     * Returns an array of the holidays in the give range
     *
     * TODO: check if the range spans to more then one option record (year)
     *
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getHolidays($start_date, $end_date)
    {
        $holidays = $this->getOption('holidays', $start_date);
        $re = [];
        foreach ($holidays as $day) {
            if ($day[0] >= $start_date && $day[0] <= $end_date) {
                $re[$day[0]] = $day[1];
            }
        }

        return $re;
    }
}
