<?php

namespace JCSGYK\AdminBundle\Services;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

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
     * Returns the selected parameter, or all if no $id given
     * If unknown $id received, it will be sent back unchanged
     *
     * @param integer $id
     * @return mixed
     */
    public function get($id = null)
    {
        if (empty($this->parameterList)) {
            $this->parameterList = [];
            $params = $this->getAll();
            foreach ($params as $para) {
                $this->parameterList[$para->getId()] = $para->getName();
            }
        }

        return is_null($id) ? $this->parameterList : (isset($this->parameterList[$id]) ? $this->parameterList[$id] : $id);
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