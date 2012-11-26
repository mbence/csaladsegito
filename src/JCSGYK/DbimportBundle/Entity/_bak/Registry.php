<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Registry
 *
 * @ORM\Table(name="Registry")
 * @ORM\Entity
 */
class Registry
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Reg_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $regId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Direction", type="integer", nullable=false)
     */
    private $direction;

    /**
     * @var integer
     *
     * @ORM\Column(name="SeqYear", type="integer", nullable=false)
     */
    private $seqyear;

    /**
     * @var integer
     *
     * @ORM\Column(name="SeqNum", type="integer", nullable=false)
     */
    private $seqnum;

    /**
     * @var string
     *
     * @ORM\Column(name="AddressText", type="text", nullable=false)
     */
    private $addresstext;

    /**
     * @var integer
     *
     * @ORM\Column(name="Address_ID", type="integer", nullable=false)
     */
    private $addressId;

    /**
     * @var float
     *
     * @ORM\Column(name="RegDate", type="float", nullable=true)
     */
    private $regdate;

    /**
     * @var string
     *
     * @ORM\Column(name="RegDate_T", type="text", nullable=true)
     */
    private $regdateT;

    /**
     * @var string
     *
     * @ORM\Column(name="Subject", type="text", nullable=false)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="RegSign", type="text", nullable=true)
     */
    private $regsign;

    /**
     * @var string
     *
     * @ORM\Column(name="DocFile", type="text", nullable=true)
     */
    private $docfile;

    /**
     * @var integer
     *
     * @ORM\Column(name="Handled", type="integer", nullable=false)
     */
    private $handled;

    /**
     * @var float
     *
     * @ORM\Column(name="HandledOn", type="float", nullable=true)
     */
    private $handledon;

    /**
     * @var integer
     *
     * @ORM\Column(name="HandledTo_ID", type="integer", nullable=false)
     */
    private $handledtoId;

    /**
     * @var integer
     *
     * @ORM\Column(name="HandledFrom_ID", type="integer", nullable=false)
     */
    private $handledfromId;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpenedBy_ID", type="integer", nullable=false)
     */
    private $openedbyId;

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
     * @ORM\Column(name="ValidItem", type="integer", nullable=false)
     */
    private $validitem;



    /**
     * Get regId
     *
     * @return integer 
     */
    public function getRegId()
    {
        return $this->regId;
    }

    /**
     * Set direction
     *
     * @param integer $direction
     * @return Registry
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    
        return $this;
    }

    /**
     * Get direction
     *
     * @return integer 
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Set seqyear
     *
     * @param integer $seqyear
     * @return Registry
     */
    public function setSeqyear($seqyear)
    {
        $this->seqyear = $seqyear;
    
        return $this;
    }

    /**
     * Get seqyear
     *
     * @return integer 
     */
    public function getSeqyear()
    {
        return $this->seqyear;
    }

    /**
     * Set seqnum
     *
     * @param integer $seqnum
     * @return Registry
     */
    public function setSeqnum($seqnum)
    {
        $this->seqnum = $seqnum;
    
        return $this;
    }

    /**
     * Get seqnum
     *
     * @return integer 
     */
    public function getSeqnum()
    {
        return $this->seqnum;
    }

    /**
     * Set addresstext
     *
     * @param string $addresstext
     * @return Registry
     */
    public function setAddresstext($addresstext)
    {
        $this->addresstext = $addresstext;
    
        return $this;
    }

    /**
     * Get addresstext
     *
     * @return string 
     */
    public function getAddresstext()
    {
        return $this->addresstext;
    }

    /**
     * Set addressId
     *
     * @param integer $addressId
     * @return Registry
     */
    public function setAddressId($addressId)
    {
        $this->addressId = $addressId;
    
        return $this;
    }

    /**
     * Get addressId
     *
     * @return integer 
     */
    public function getAddressId()
    {
        return $this->addressId;
    }

    /**
     * Set regdate
     *
     * @param float $regdate
     * @return Registry
     */
    public function setRegdate($regdate)
    {
        $this->regdate = $regdate;
    
        return $this;
    }

    /**
     * Get regdate
     *
     * @return float 
     */
    public function getRegdate()
    {
        return $this->regdate;
    }

    /**
     * Set regdateT
     *
     * @param string $regdateT
     * @return Registry
     */
    public function setRegdateT($regdateT)
    {
        $this->regdateT = $regdateT;
    
        return $this;
    }

    /**
     * Get regdateT
     *
     * @return string 
     */
    public function getRegdateT()
    {
        return $this->regdateT;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return Registry
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    
        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Registry
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set regsign
     *
     * @param string $regsign
     * @return Registry
     */
    public function setRegsign($regsign)
    {
        $this->regsign = $regsign;
    
        return $this;
    }

    /**
     * Get regsign
     *
     * @return string 
     */
    public function getRegsign()
    {
        return $this->regsign;
    }

    /**
     * Set docfile
     *
     * @param string $docfile
     * @return Registry
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

    /**
     * Set handled
     *
     * @param integer $handled
     * @return Registry
     */
    public function setHandled($handled)
    {
        $this->handled = $handled;
    
        return $this;
    }

    /**
     * Get handled
     *
     * @return integer 
     */
    public function getHandled()
    {
        return $this->handled;
    }

    /**
     * Set handledon
     *
     * @param float $handledon
     * @return Registry
     */
    public function setHandledon($handledon)
    {
        $this->handledon = $handledon;
    
        return $this;
    }

    /**
     * Get handledon
     *
     * @return float 
     */
    public function getHandledon()
    {
        return $this->handledon;
    }

    /**
     * Set handledtoId
     *
     * @param integer $handledtoId
     * @return Registry
     */
    public function setHandledtoId($handledtoId)
    {
        $this->handledtoId = $handledtoId;
    
        return $this;
    }

    /**
     * Get handledtoId
     *
     * @return integer 
     */
    public function getHandledtoId()
    {
        return $this->handledtoId;
    }

    /**
     * Set handledfromId
     *
     * @param integer $handledfromId
     * @return Registry
     */
    public function setHandledfromId($handledfromId)
    {
        $this->handledfromId = $handledfromId;
    
        return $this;
    }

    /**
     * Get handledfromId
     *
     * @return integer 
     */
    public function getHandledfromId()
    {
        return $this->handledfromId;
    }

    /**
     * Set openedbyId
     *
     * @param integer $openedbyId
     * @return Registry
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
     * Set createdon
     *
     * @param float $createdon
     * @return Registry
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
     * @return Registry
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
     * @return Registry
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
     * @return Registry
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
     * Set validitem
     *
     * @param integer $validitem
     * @return Registry
     */
    public function setValiditem($validitem)
    {
        $this->validitem = $validitem;
    
        return $this;
    }

    /**
     * Get validitem
     *
     * @return integer 
     */
    public function getValiditem()
    {
        return $this->validitem;
    }
}