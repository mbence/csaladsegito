<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Calendar
 *
 * @ORM\Table(name="Calendar")
 * @ORM\Entity
 */
class Calendar
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Calendar_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $calendarId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Employe_ID", type="integer", nullable=false)
     */
    private $employeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Client_ID", type="integer", nullable=false)
     */
    private $clientId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Problem_ID", type="integer", nullable=false)
     */
    private $problemId;

    /**
     * @var float
     *
     * @ORM\Column(name="EDate", type="float", nullable=false)
     */
    private $edate;

    /**
     * @var integer
     *
     * @ORM\Column(name="Status", type="integer", nullable=false)
     */
    private $status;



    /**
     * Get calendarId
     *
     * @return integer 
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * Set employeId
     *
     * @param integer $employeId
     * @return Calendar
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
     * Set clientId
     *
     * @param integer $clientId
     * @return Calendar
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
     * Set problemId
     *
     * @param integer $problemId
     * @return Calendar
     */
    public function setProblemId($problemId)
    {
        $this->problemId = $problemId;
    
        return $this;
    }

    /**
     * Get problemId
     *
     * @return integer 
     */
    public function getProblemId()
    {
        return $this->problemId;
    }

    /**
     * Set edate
     *
     * @param float $edate
     * @return Calendar
     */
    public function setEdate($edate)
    {
        $this->edate = $edate;
    
        return $this;
    }

    /**
     * Get edate
     *
     * @return float 
     */
    public function getEdate()
    {
        return $this->edate;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Calendar
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }
}