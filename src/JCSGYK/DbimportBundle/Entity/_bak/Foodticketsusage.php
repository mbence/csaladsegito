<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Foodticketsusage
 *
 * @ORM\Table(name="FoodTicketsUsage")
 * @ORM\Entity
 */
class Foodticketsusage
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Usage_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $usageId;

    /**
     * @var integer
     *
     * @ORM\Column(name="FoodTicket_ID", type="integer", nullable=false)
     */
    private $foodticketId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Client_ID", type="integer", nullable=false)
     */
    private $clientId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Employe_ID", type="integer", nullable=false)
     */
    private $employeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpCode", type="integer", nullable=false)
     */
    private $opcode;

    /**
     * @var float
     *
     * @ORM\Column(name="OpDate", type="float", nullable=false)
     */
    private $opdate;

    /**
     * @var string
     *
     * @ORM\Column(name="OpDate_T", type="text", nullable=true)
     */
    private $opdateT;



    /**
     * Get usageId
     *
     * @return integer 
     */
    public function getUsageId()
    {
        return $this->usageId;
    }

    /**
     * Set foodticketId
     *
     * @param integer $foodticketId
     * @return Foodticketsusage
     */
    public function setFoodticketId($foodticketId)
    {
        $this->foodticketId = $foodticketId;
    
        return $this;
    }

    /**
     * Get foodticketId
     *
     * @return integer 
     */
    public function getFoodticketId()
    {
        return $this->foodticketId;
    }

    /**
     * Set clientId
     *
     * @param integer $clientId
     * @return Foodticketsusage
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    
        return $this;
    }

    /**
     * Get clientId
     *
     * @return integer 
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set employeId
     *
     * @param integer $employeId
     * @return Foodticketsusage
     */
    public function setEmployeId($employeId)
    {
        $this->employeId = $employeId;
    
        return $this;
    }

    /**
     * Get employeId
     *
     * @return integer 
     */
    public function getEmployeId()
    {
        return $this->employeId;
    }

    /**
     * Set opcode
     *
     * @param integer $opcode
     * @return Foodticketsusage
     */
    public function setOpcode($opcode)
    {
        $this->opcode = $opcode;
    
        return $this;
    }

    /**
     * Get opcode
     *
     * @return integer 
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * Set opdate
     *
     * @param float $opdate
     * @return Foodticketsusage
     */
    public function setOpdate($opdate)
    {
        $this->opdate = $opdate;
    
        return $this;
    }

    /**
     * Get opdate
     *
     * @return float 
     */
    public function getOpdate()
    {
        return $this->opdate;
    }

    /**
     * Set opdateT
     *
     * @param string $opdateT
     * @return Foodticketsusage
     */
    public function setOpdateT($opdateT)
    {
        $this->opdateT = $opdateT;
    
        return $this;
    }

    /**
     * Get opdateT
     *
     * @return string 
     */
    public function getOpdateT()
    {
        return $this->opdateT;
    }
}