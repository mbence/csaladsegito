<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Classpersons
 *
 * @ORM\Table(name="ClassPersons")
 * @ORM\Entity
 */
class Classpersons
{
    /**
     * @var integer
     *
     * @ORM\Column(name="CP_ID", type="integer", nullable=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $cpId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Class_ID", type="integer", nullable=false)
     */
    private $classId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Person_ID", type="integer", nullable=false)
     */
    private $personId;

    /**
     * @var integer
     *
     * @ORM\Column(name="VisitState", type="integer", nullable=false)
     */
    private $visitstate;



    /**
     * Get cpId
     *
     * @return integer 
     */
    public function getCpId()
    {
        return $this->cpId;
    }

    /**
     * Set classId
     *
     * @param integer $classId
     * @return Classpersons
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
    
        return $this;
    }

    /**
     * Get classId
     *
     * @return integer 
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * Set personId
     *
     * @param integer $personId
     * @return Classpersons
     */
    public function setPersonId($personId)
    {
        $this->personId = $personId;
    
        return $this;
    }

    /**
     * Get personId
     *
     * @return integer 
     */
    public function getPersonId()
    {
        return $this->personId;
    }

    /**
     * Set visitstate
     *
     * @param integer $visitstate
     * @return Classpersons
     */
    public function setVisitstate($visitstate)
    {
        $this->visitstate = $visitstate;
    
        return $this;
    }

    /**
     * Get visitstate
     *
     * @return integer 
     */
    public function getVisitstate()
    {
        return $this->visitstate;
    }
}