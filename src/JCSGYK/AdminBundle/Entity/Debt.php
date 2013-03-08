<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Debt
 *
 * @ORM\Table(name="debt")
 * @ORM\Entity
 */
class Debt
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Problem
     *
     * @ORM\ManyToOne(targetEntity="Problem", inversedBy="debts")
     * @ORM\JoinColumn(name="problem_id", referencedColumnName="id")
     */
    private $problem;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint", nullable=true)
     */
    private $type;

    /**
     * @var float
     *
     * @ORM\Column(name="registered_debt", type="decimal", nullable=true)
     */
    private $registeredDebt;

    /**
     * @var float
     *
     * @ORM\Column(name="managed_debt", type="decimal", nullable=true)
     */
    private $managedDebt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;



    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Debt
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set registeredDebt
     *
     * @param float $registeredDebt
     * @return Debt
     */
    public function setRegisteredDebt($registeredDebt)
    {
        $this->registeredDebt = $registeredDebt;

        return $this;
    }

    /**
     * Get registeredDebt
     *
     * @return float
     */
    public function getRegisteredDebt()
    {
        return $this->registeredDebt;
    }

    /**
     * Set managedDebt
     *
     * @param float $managedDebt
     * @return Debt
     */
    public function setManagedDebt($managedDebt)
    {
        $this->managedDebt = $managedDebt;

        return $this;
    }

    /**
     * Get managedDebt
     *
     * @return float
     */
    public function getManagedDebt()
    {
        return $this->managedDebt;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Debt
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set problem
     *
     * @param \JCSGYK\AdminBundle\Entity\Problem $problem
     * @return Debt
     */
    public function setProblem(\JCSGYK\AdminBundle\Entity\Problem $problem = null)
    {
        $this->problem = $problem;
    
        return $this;
    }

    /**
     * Get problem
     *
     * @return \JCSGYK\AdminBundle\Entity\Problem 
     */
    public function getProblem()
    {
        return $this->problem;
    }
}