<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Publicfoodtending
 *
 * @ORM\Table(name="PublicFoodTending")
 * @ORM\Entity
 */
class Publicfoodtending
{
    /**
     * @var integer
     *
     * @ORM\Column(name="PFT_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $pftId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Problem_ID", type="integer", nullable=false)
     */
    private $problemId;

    /**
     * @var float
     *
     * @ORM\Column(name="YearMonth", type="float", nullable=false)
     */
    private $yearmonth;

    /**
     * @var integer
     *
     * @ORM\Column(name="PersonCnt", type="integer", nullable=false)
     */
    private $personcnt;



    /**
     * Get pftId
     *
     * @return integer 
     */
    public function getPftId()
    {
        return $this->pftId;
    }

    /**
     * Set problemId
     *
     * @param integer $problemId
     * @return Publicfoodtending
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
     * Set yearmonth
     *
     * @param float $yearmonth
     * @return Publicfoodtending
     */
    public function setYearmonth($yearmonth)
    {
        $this->yearmonth = $yearmonth;
    
        return $this;
    }

    /**
     * Get yearmonth
     *
     * @return float 
     */
    public function getYearmonth()
    {
        return $this->yearmonth;
    }

    /**
     * Set personcnt
     *
     * @param integer $personcnt
     * @return Publicfoodtending
     */
    public function setPersoncnt($personcnt)
    {
        $this->personcnt = $personcnt;
    
        return $this;
    }

    /**
     * Get personcnt
     *
     * @return integer 
     */
    public function getPersoncnt()
    {
        return $this->personcnt;
    }
}