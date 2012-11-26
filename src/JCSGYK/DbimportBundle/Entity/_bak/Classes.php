<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Classes
 *
 * @ORM\Table(name="Classes")
 * @ORM\Entity
 */
class Classes
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Class_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $classId;

    /**
     * @var string
     *
     * @ORM\Column(name="Note", type="text", nullable=true)
     */
    private $note;

    /**
     * @var string
     *
     * @ORM\Column(name="ClassDate", type="text", nullable=true)
     */
    private $classdate;

    /**
     * @var string
     *
     * @ORM\Column(name="ClassDate_T", type="text", nullable=true)
     */
    private $classdateT;

    /**
     * @var integer
     *
     * @ORM\Column(name="HeldBy1_ID", type="integer", nullable=false)
     */
    private $heldby1Id;

    /**
     * @var integer
     *
     * @ORM\Column(name="HeldBy2_ID", type="integer", nullable=false)
     */
    private $heldby2Id;

    /**
     * @var integer
     *
     * @ORM\Column(name="Status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var float
     *
     * @ORM\Column(name="CreatedOn", type="float", nullable=true)
     */
    private $createdon;

    /**
     * @var integer
     *
     * @ORM\Column(name="CreatedBy_ID", type="integer", nullable=false)
     */
    private $createdbyId;

    /**
     * @var float
     *
     * @ORM\Column(name="ModifiedOn", type="float", nullable=true)
     */
    private $modifiedon;

    /**
     * @var integer
     *
     * @ORM\Column(name="ModifiedBy_ID", type="integer", nullable=false)
     */
    private $modifiedbyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpenedBy_ID", type="integer", nullable=true)
     */
    private $openedbyId;

    /**
     * @var string
     *
     * @ORM\Column(name="DocFile", type="text", nullable=true)
     */
    private $docfile;



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
     * Set note
     *
     * @param string $note
     * @return Classes
     */
    public function setNote($note)
    {
        $this->note = $note;
    
        return $this;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set classdate
     *
     * @param string $classdate
     * @return Classes
     */
    public function setClassdate($classdate)
    {
        $this->classdate = $classdate;
    
        return $this;
    }

    /**
     * Get classdate
     *
     * @return string 
     */
    public function getClassdate()
    {
        return $this->classdate;
    }

    /**
     * Set classdateT
     *
     * @param string $classdateT
     * @return Classes
     */
    public function setClassdateT($classdateT)
    {
        $this->classdateT = $classdateT;
    
        return $this;
    }

    /**
     * Get classdateT
     *
     * @return string 
     */
    public function getClassdateT()
    {
        return $this->classdateT;
    }

    /**
     * Set heldby1Id
     *
     * @param integer $heldby1Id
     * @return Classes
     */
    public function setHeldby1Id($heldby1Id)
    {
        $this->heldby1Id = $heldby1Id;
    
        return $this;
    }

    /**
     * Get heldby1Id
     *
     * @return integer 
     */
    public function getHeldby1Id()
    {
        return $this->heldby1Id;
    }

    /**
     * Set heldby2Id
     *
     * @param integer $heldby2Id
     * @return Classes
     */
    public function setHeldby2Id($heldby2Id)
    {
        $this->heldby2Id = $heldby2Id;
    
        return $this;
    }

    /**
     * Get heldby2Id
     *
     * @return integer 
     */
    public function getHeldby2Id()
    {
        return $this->heldby2Id;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Classes
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

    /**
     * Set createdon
     *
     * @param float $createdon
     * @return Classes
     */
    public function setCreatedon($createdon)
    {
        $this->createdon = $createdon;
    
        return $this;
    }

    /**
     * Get createdon
     *
     * @return float 
     */
    public function getCreatedon()
    {
        return $this->createdon;
    }

    /**
     * Set createdbyId
     *
     * @param integer $createdbyId
     * @return Classes
     */
    public function setCreatedbyId($createdbyId)
    {
        $this->createdbyId = $createdbyId;
    
        return $this;
    }

    /**
     * Get createdbyId
     *
     * @return integer 
     */
    public function getCreatedbyId()
    {
        return $this->createdbyId;
    }

    /**
     * Set modifiedon
     *
     * @param float $modifiedon
     * @return Classes
     */
    public function setModifiedon($modifiedon)
    {
        $this->modifiedon = $modifiedon;
    
        return $this;
    }

    /**
     * Get modifiedon
     *
     * @return float 
     */
    public function getModifiedon()
    {
        return $this->modifiedon;
    }

    /**
     * Set modifiedbyId
     *
     * @param integer $modifiedbyId
     * @return Classes
     */
    public function setModifiedbyId($modifiedbyId)
    {
        $this->modifiedbyId = $modifiedbyId;
    
        return $this;
    }

    /**
     * Get modifiedbyId
     *
     * @return integer 
     */
    public function getModifiedbyId()
    {
        return $this->modifiedbyId;
    }

    /**
     * Set openedbyId
     *
     * @param integer $openedbyId
     * @return Classes
     */
    public function setOpenedbyId($openedbyId)
    {
        $this->openedbyId = $openedbyId;
    
        return $this;
    }

    /**
     * Get openedbyId
     *
     * @return integer 
     */
    public function getOpenedbyId()
    {
        return $this->openedbyId;
    }

    /**
     * Set docfile
     *
     * @param string $docfile
     * @return Classes
     */
    public function setDocfile($docfile)
    {
        $this->docfile = $docfile;
    
        return $this;
    }

    /**
     * Get docfile
     *
     * @return string 
     */
    public function getDocfile()
    {
        return $this->docfile;
    }
}