<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Enqueries
 *
 * @ORM\Table(name="Enqueries")
 * @ORM\Entity
 */
class Enqueries
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Enquery_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $enqueryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Type1", type="integer", nullable=true)
     */
    private $type1;

    /**
     * @var integer
     *
     * @ORM\Column(name="Type2", type="integer", nullable=true)
     */
    private $type2;

    /**
     * @var float
     *
     * @ORM\Column(name="EnqDate", type="float", nullable=false)
     */
    private $enqdate;

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
     * @var string
     *
     * @ORM\Column(name="EnqDate_T", type="text", nullable=false)
     */
    private $enqdateT;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpenedBy_ID", type="integer", nullable=true)
     */
    private $openedbyId;



    /**
     * Get enqueryId
     *
     * @return integer 
     */
    public function getEnqueryId()
    {
        return $this->enqueryId;
    }

    /**
     * Set type1
     *
     * @param integer $type1
     * @return Enqueries
     */
    public function setType1($type1)
    {
        $this->type1 = $type1;
    
        return $this;
    }

    /**
     * Get type1
     *
     * @return integer 
     */
    public function getType1()
    {
        return $this->type1;
    }

    /**
     * Set type2
     *
     * @param integer $type2
     * @return Enqueries
     */
    public function setType2($type2)
    {
        $this->type2 = $type2;
    
        return $this;
    }

    /**
     * Get type2
     *
     * @return integer 
     */
    public function getType2()
    {
        return $this->type2;
    }

    /**
     * Set enqdate
     *
     * @param float $enqdate
     * @return Enqueries
     */
    public function setEnqdate($enqdate)
    {
        $this->enqdate = $enqdate;
    
        return $this;
    }

    /**
     * Get enqdate
     *
     * @return float 
     */
    public function getEnqdate()
    {
        return $this->enqdate;
    }

    /**
     * Set createdon
     *
     * @param float $createdon
     * @return Enqueries
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
     * @return Enqueries
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
     * Set enqdateT
     *
     * @param string $enqdateT
     * @return Enqueries
     */
    public function setEnqdateT($enqdateT)
    {
        $this->enqdateT = $enqdateT;
    
        return $this;
    }

    /**
     * Get enqdateT
     *
     * @return string 
     */
    public function getEnqdateT()
    {
        return $this->enqdateT;
    }

    /**
     * Set openedbyId
     *
     * @param integer $openedbyId
     * @return Enqueries
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
}