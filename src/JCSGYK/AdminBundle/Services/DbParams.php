<?php

namespace JCSGYK\AdminBundle\Services;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

/**
 * Service for database parameter retrieval
 */
class DbParams
{
    private $parameters;

    private $parameterList = [];
    private $groups = [];

    private $doctrine;

    public function __construct(Doctrine $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Returns all parameters in a doctrine object collection
     *
     * @return array $parameters
     */
    public function getAll()
    {
        if (empty($this->parameters)) {
            $this->parameters = $this->doctrine->getManager()
                ->getRepository('JCSGYKAdminBundle:Parameter')
                ->getAll();
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
     * @return mixed
     */
    public function getGroup($id = null)
    {
        if (empty($this->groups)) {
            $this->groups = [];
            $params = $this->getAll();

            foreach ($params as $para) {
                if (empty($this->groups[$para->getGroup()])) {
                    $this->groups[$para->getGroup()] = [];
                }
                $this->groups[$para->getGroup()][$para->getId()] = $para->getName();
            }
        }

        return is_null($id) ? $this->groups : (isset($this->groups[$id]) ? $this->groups[$id] : false);
    }
}