<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Groupclasses
 *
 * @ORM\Table(name="GroupClasses")
 * @ORM\Entity
 */
class Groupclasses
{
    /**
     * @var integer
     *
     * @ORM\Column(name="GC_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $gcId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Group_ID", type="integer", nullable=false)
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Class_ID", type="integer", nullable=false)
     */
    private $classId;



    /**
     * Get gcId
     *
     * @return integer 
     */
    public function getGcId()
    {
        return $this->gcId;
    }

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return Groupclasses
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
     * Set classId
     *
     * @param integer $classId
     * @return Groupclasses
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
}