<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Users
 *
 * @ORM\Table(name="Users")
 * @ORM\Entity
 */
class Users
{
    /**
     * @var integer
     *
     * @ORM\Column(name="UJFK", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $ujfk;

    /**
     * @var integer
     *
     * @ORM\Column(name="PJFIG", type="integer", nullable=false)
     */
    private $pjfig;

    /**
     * @var string
     *
     * @ORM\Column(name="IKFL", type="text", nullable=false)
     */
    private $ikfl;

    /**
     * @var string
     *
     * @ORM\Column(name="VHRD", type="text", nullable=false)
     */
    private $vhrd;

    /**
     * @var integer
     *
     * @ORM\Column(name="DFGF", type="integer", nullable=false)
     */
    private $dfgf;

    /**
     * @var integer
     *
     * @ORM\Column(name="RGHI", type="integer", nullable=false)
     */
    private $rghi;

    /**
     * @var float
     *
     * @ORM\Column(name="LLT", type="float", nullable=true)
     */
    private $llt;

    /**
     * @var string
     *
     * @ORM\Column(name="LL_T", type="text", nullable=true)
     */
    private $llT2;

    /**
     * @var float
     *
     * @ORM\Column(name="XDF", type="float", nullable=true)
     */
    private $xdf;

    /**
     * @var string
     *
     * @ORM\Column(name="XD_T", type="text", nullable=true)
     */
    private $xdT;

    /**
     * @var string
     *
     * @ORM\Column(name="HNM", type="text", nullable=true)
     */
    private $hnm;



    /**
     * Get ujfk
     *
     * @return integer 
     */
    public function getUjfk()
    {
        return $this->ujfk;
    }

    /**
     * Set pjfig
     *
     * @param integer $pjfig
     * @return Users
     */
    public function setPjfig($pjfig)
    {
        $this->pjfig = $pjfig;
    
        return $this;
    }

    /**
     * Get pjfig
     *
     * @return integer 
     */
    public function getPjfig()
    {
        return $this->pjfig;
    }

    /**
     * Set ikfl
     *
     * @param string $ikfl
     * @return Users
     */
    public function setIkfl($ikfl)
    {
        $this->ikfl = $ikfl;
    
        return $this;
    }

    /**
     * Get ikfl
     *
     * @return string 
     */
    public function getIkfl()
    {
        return $this->ikfl;
    }

    /**
     * Set vhrd
     *
     * @param string $vhrd
     * @return Users
     */
    public function setVhrd($vhrd)
    {
        $this->vhrd = $vhrd;
    
        return $this;
    }

    /**
     * Get vhrd
     *
     * @return string 
     */
    public function getVhrd()
    {
        return $this->vhrd;
    }

    /**
     * Set dfgf
     *
     * @param integer $dfgf
     * @return Users
     */
    public function setDfgf($dfgf)
    {
        $this->dfgf = $dfgf;
    
        return $this;
    }

    /**
     * Get dfgf
     *
     * @return integer 
     */
    public function getDfgf()
    {
        return $this->dfgf;
    }

    /**
     * Set rghi
     *
     * @param integer $rghi
     * @return Users
     */
    public function setRghi($rghi)
    {
        $this->rghi = $rghi;
    
        return $this;
    }

    /**
     * Get rghi
     *
     * @return integer 
     */
    public function getRghi()
    {
        return $this->rghi;
    }

    /**
     * Set llt
     *
     * @param float $llt
     * @return Users
     */
    public function setLlt($llt)
    {
        $this->llt = $llt;
    
        return $this;
    }

    /**
     * Get llt
     *
     * @return float 
     */
    public function getLlt()
    {
        return $this->llt;
    }

    /**
     * Set llT2
     *
     * @param string $llT2
     * @return Users
     */
    public function setLlT2($llT2)
    {
        $this->llT2 = $llT2;
    
        return $this;
    }

    /**
     * Get llT2
     *
     * @return string 
     */
    public function getLlT2()
    {
        return $this->llT2;
    }

    /**
     * Set xdf
     *
     * @param float $xdf
     * @return Users
     */
    public function setXdf($xdf)
    {
        $this->xdf = $xdf;
    
        return $this;
    }

    /**
     * Get xdf
     *
     * @return float 
     */
    public function getXdf()
    {
        return $this->xdf;
    }

    /**
     * Set xdT
     *
     * @param string $xdT
     * @return Users
     */
    public function setXdT($xdT)
    {
        $this->xdT = $xdT;
    
        return $this;
    }

    /**
     * Get xdT
     *
     * @return string 
     */
    public function getXdT()
    {
        return $this->xdT;
    }

    /**
     * Set hnm
     *
     * @param string $hnm
     * @return Users
     */
    public function setHnm($hnm)
    {
        $this->hnm = $hnm;
    
        return $this;
    }

    /**
     * Get hnm
     *
     * @return string 
     */
    public function getHnm()
    {
        return $this->hnm;
    }
}