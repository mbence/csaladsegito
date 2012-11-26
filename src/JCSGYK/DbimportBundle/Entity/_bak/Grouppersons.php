<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Grouppersons
 *
 * @ORM\Table(name="GroupPersons")
 * @ORM\Entity
 */
class Grouppersons
{
    /**
     * @var integer
     *
     * @ORM\Column(name="GP_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $gpId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Group_ID", type="integer", nullable=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Person_ID", type="integer", nullable=false)
     */
    private $personId;

    /**
     * @var integer
     *
     * @ORM\Column(name="AssigmentType", type="integer", nullable=true)
     */
    private $assigmenttype;



    /**
     * Get gpId
     *
     * @return integer 
     */
    public function getGpId()
    {
        return $this->gpId;
    }

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return Grouppersons
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    
        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer 
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set personId
     *
     * @param integer $personId
     * @return Grouppersons
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
     * Set assigmenttype
     *
     * @param integer $assigmenttype
     * @return Grouppersons
     */
    public function setAssigmenttype($assigmenttype)
    {
        $this->assigmenttype = $assigmenttype;
    
        return $this;
    }

    /**
     * Get assigmenttype
     *
     * @return integer 
     */
    public function getAssigmenttype()
    {
        return $this->assigmenttype;
    }
}