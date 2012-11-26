<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Problems
 *
 * @ORM\Table(name="Problems")
 * @ORM\Entity
 */
class Problems
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Problem_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $problemId;

    /**
     * @var string
     *
     * @ORM\Column(name="Title", type="text", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="Desciption", type="text", nullable=true)
     */
    private $desciption;

    /**
     * @var integer
     *
     * @ORM\Column(name="UserProb", type="integer", nullable=true)
     */
    private $userprob;

    /**
     * @var integer
     *
     * @ORM\Column(name="RealProb", type="integer", nullable=true)
     */
    private $realprob;

    /**
     * @var integer
     *
     * @ORM\Column(name="ProblemLevel", type="integer", nullable=false)
     */
    private $problemlevel;

    /**
     * @var integer
     *
     * @ORM\Column(name="Status", type="integer", nullable=true)
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
     * @ORM\Column(name="AssignedTo_ID", type="integer", nullable=false)
     */
    private $assignedtoId;

    /**
     * @var integer
     *
     * @ORM\Column(name="ClosedBy_ID", type="integer", nullable=false)
     */
    private $closedbyId;

    /**
     * @var float
     *
     * @ORM\Column(name="ClosedOn", type="float", nullable=true)
     */
    private $closedon;

    /**
     * @var integer
     *
     * @ORM\Column(name="CloseCode", type="integer", nullable=true)
     */
    private $closecode;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpenedBy_ID", type="integer", nullable=false)
     */
    private $openedbyId;

    /**
     * @var string
     *
     * @ORM\Column(name="DocFile", type="text", nullable=true)
     */
    private $docfile;

    /**
     * @var integer
     *
     * @ORM\Column(name="Visible", type="integer", nullable=true)
     */
    private $visible;

    /**
     * @var string
     *
     * @ORM\Column(name="FieldText", type="text", nullable=true)
     */
    private $fieldtext;

    /**
     * @var integer
     *
     * @ORM\Column(name="FieldNum", type="integer", nullable=true)
     */
    private $fieldnum;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhJVK_1", type="integer", nullable=true)
     */
    private $dhjvk1;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhJVK_2", type="integer", nullable=true)
     */
    private $dhjvk2;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhElmu_1", type="integer", nullable=true)
     */
    private $dhelmu1;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhElmu_2", type="integer", nullable=true)
     */
    private $dhelmu2;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhFoGaz_1", type="integer", nullable=true)
     */
    private $dhfogaz1;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhFoGaz_2", type="integer", nullable=true)
     */
    private $dhfogaz2;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhFotav_1", type="integer", nullable=true)
     */
    private $dhfotav1;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhFotav_2", type="integer", nullable=true)
     */
    private $dhfotav2;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhDijbeszedo_1", type="integer", nullable=true)
     */
    private $dhdijbeszedo1;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhDijbeszedo_2", type="integer", nullable=true)
     */
    private $dhdijbeszedo2;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhKozos_1", type="integer", nullable=true)
     */
    private $dhkozos1;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhKozos_2", type="integer", nullable=true)
     */
    private $dhkozos2;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhJVK_3", type="integer", nullable=true)
     */
    private $dhjvk3;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhElmu_3", type="integer", nullable=true)
     */
    private $dhelmu3;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhFoGaz_3", type="integer", nullable=true)
     */
    private $dhfogaz3;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhFotav_3", type="integer", nullable=true)
     */
    private $dhfotav3;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhDijbeszedo_3", type="integer", nullable=true)
     */
    private $dhdijbeszedo3;

    /**
     * @var integer
     *
     * @ORM\Column(name="dhKozos_3", type="integer", nullable=true)
     */
    private $dhkozos3;



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
     * Set title
     *
     * @param string $title
     * @return Problems
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set desciption
     *
     * @param string $desciption
     * @return Problems
     */
    public function setDesciption($desciption)
    {
        $this->desciption = $desciption;
    
        return $this;
    }

    /**
     * Get desciption
     *
     * @return string 
     */
    public function getDesciption()
    {
        return $this->desciption;
    }

    /**
     * Set userprob
     *
     * @param integer $userprob
     * @return Problems
     */
    public function setUserprob($userprob)
    {
        $this->userprob = $userprob;
    
        return $this;
    }

    /**
     * Get userprob
     *
     * @return integer 
     */
    public function getUserprob()
    {
        return $this->userprob;
    }

    /**
     * Set realprob
     *
     * @param integer $realprob
     * @return Problems
     */
    public function setRealprob($realprob)
    {
        $this->realprob = $realprob;
    
        return $this;
    }

    /**
     * Get realprob
     *
     * @return integer 
     */
    public function getRealprob()
    {
        return $this->realprob;
    }

    /**
     * Set problemlevel
     *
     * @param integer $problemlevel
     * @return Problems
     */
    public function setProblemlevel($problemlevel)
    {
        $this->problemlevel = $problemlevel;
    
        return $this;
    }

    /**
     * Get problemlevel
     *
     * @return integer 
     */
    public function getProblemlevel()
    {
        return $this->problemlevel;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Problems
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
     * @return Problems
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
     * @return Problems
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
     * @return Problems
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
     * @return Problems
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
     * Set assignedtoId
     *
     * @param integer $assignedtoId
     * @return Problems
     */
    public function setAssignedtoId($assignedtoId)
    {
        $this->assignedtoId = $assignedtoId;
    
        return $this;
    }

    /**
     * Get assignedtoId
     *
     * @return integer 
     */
    public function getAssignedtoId()
    {
        return $this->assignedtoId;
    }

    /**
     * Set closedbyId
     *
     * @param integer $closedbyId
     * @return Problems
     */
    public function setClosedbyId($closedbyId)
    {
        $this->closedbyId = $closedbyId;
    
        return $this;
    }

    /**
     * Get closedbyId
     *
     * @return integer 
     */
    public function getClosedbyId()
    {
        return $this->closedbyId;
    }

    /**
     * Set closedon
     *
     * @param float $closedon
     * @return Problems
     */
    public function setClosedon($closedon)
    {
        $this->closedon = $closedon;
    
        return $this;
    }

    /**
     * Get closedon
     *
     * @return float 
     */
    public function getClosedon()
    {
        return $this->closedon;
    }

    /**
     * Set closecode
     *
     * @param integer $closecode
     * @return Problems
     */
    public function setClosecode($closecode)
    {
        $this->closecode = $closecode;
    
        return $this;
    }

    /**
     * Get closecode
     *
     * @return integer 
     */
    public function getClosecode()
    {
        return $this->closecode;
    }

    /**
     * Set openedbyId
     *
     * @param integer $openedbyId
     * @return Problems
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
     * @return Problems
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
     * Set visible
     *
     * @param integer $visible
     * @return Problems
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
    
        return $this;
    }

    /**
     * Get visible
     *
     * @return integer 
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set fieldtext
     *
     * @param string $fieldtext
     * @return Problems
     */
    public function setFieldtext($fieldtext)
    {
        $this->fieldtext = $fieldtext;
    
        return $this;
    }

    /**
     * Get fieldtext
     *
     * @return string 
     */
    public function getFieldtext()
    {
        return $this->fieldtext;
    }

    /**
     * Set fieldnum
     *
     * @param integer $fieldnum
     * @return Problems
     */
    public function setFieldnum($fieldnum)
    {
        $this->fieldnum = $fieldnum;
    
        return $this;
    }

    /**
     * Get fieldnum
     *
     * @return integer 
     */
    public function getFieldnum()
    {
        return $this->fieldnum;
    }

    /**
     * Set dhjvk1
     *
     * @param integer $dhjvk1
     * @return Problems
     */
    public function setDhjvk1($dhjvk1)
    {
        $this->dhjvk1 = $dhjvk1;
    
        return $this;
    }

    /**
     * Get dhjvk1
     *
     * @return integer 
     */
    public function getDhjvk1()
    {
        return $this->dhjvk1;
    }

    /**
     * Set dhjvk2
     *
     * @param integer $dhjvk2
     * @return Problems
     */
    public function setDhjvk2($dhjvk2)
    {
        $this->dhjvk2 = $dhjvk2;
    
        return $this;
    }

    /**
     * Get dhjvk2
     *
     * @return integer 
     */
    public function getDhjvk2()
    {
        return $this->dhjvk2;
    }

    /**
     * Set dhelmu1
     *
     * @param integer $dhelmu1
     * @return Problems
     */
    public function setDhelmu1($dhelmu1)
    {
        $this->dhelmu1 = $dhelmu1;
    
        return $this;
    }

    /**
     * Get dhelmu1
     *
     * @return integer 
     */
    public function getDhelmu1()
    {
        return $this->dhelmu1;
    }

    /**
     * Set dhelmu2
     *
     * @param integer $dhelmu2
     * @return Problems
     */
    public function setDhelmu2($dhelmu2)
    {
        $this->dhelmu2 = $dhelmu2;
    
        return $this;
    }

    /**
     * Get dhelmu2
     *
     * @return integer 
     */
    public function getDhelmu2()
    {
        return $this->dhelmu2;
    }

    /**
     * Set dhfogaz1
     *
     * @param integer $dhfogaz1
     * @return Problems
     */
    public function setDhfogaz1($dhfogaz1)
    {
        $this->dhfogaz1 = $dhfogaz1;
    
        return $this;
    }

    /**
     * Get dhfogaz1
     *
     * @return integer 
     */
    public function getDhfogaz1()
    {
        return $this->dhfogaz1;
    }

    /**
     * Set dhfogaz2
     *
     * @param integer $dhfogaz2
     * @return Problems
     */
    public function setDhfogaz2($dhfogaz2)
    {
        $this->dhfogaz2 = $dhfogaz2;
    
        return $this;
    }

    /**
     * Get dhfogaz2
     *
     * @return integer 
     */
    public function getDhfogaz2()
    {
        return $this->dhfogaz2;
    }

    /**
     * Set dhfotav1
     *
     * @param integer $dhfotav1
     * @return Problems
     */
    public function setDhfotav1($dhfotav1)
    {
        $this->dhfotav1 = $dhfotav1;
    
        return $this;
    }

    /**
     * Get dhfotav1
     *
     * @return integer 
     */
    public function getDhfotav1()
    {
        return $this->dhfotav1;
    }

    /**
     * Set dhfotav2
     *
     * @param integer $dhfotav2
     * @return Problems
     */
    public function setDhfotav2($dhfotav2)
    {
        $this->dhfotav2 = $dhfotav2;
    
        return $this;
    }

    /**
     * Get dhfotav2
     *
     * @return integer 
     */
    public function getDhfotav2()
    {
        return $this->dhfotav2;
    }

    /**
     * Set dhdijbeszedo1
     *
     * @param integer $dhdijbeszedo1
     * @return Problems
     */
    public function setDhdijbeszedo1($dhdijbeszedo1)
    {
        $this->dhdijbeszedo1 = $dhdijbeszedo1;
    
        return $this;
    }

    /**
     * Get dhdijbeszedo1
     *
     * @return integer 
     */
    public function getDhdijbeszedo1()
    {
        return $this->dhdijbeszedo1;
    }

    /**
     * Set dhdijbeszedo2
     *
     * @param integer $dhdijbeszedo2
     * @return Problems
     */
    public function setDhdijbeszedo2($dhdijbeszedo2)
    {
        $this->dhdijbeszedo2 = $dhdijbeszedo2;
    
        return $this;
    }

    /**
     * Get dhdijbeszedo2
     *
     * @return integer 
     */
    public function getDhdijbeszedo2()
    {
        return $this->dhdijbeszedo2;
    }

    /**
     * Set dhkozos1
     *
     * @param integer $dhkozos1
     * @return Problems
     */
    public function setDhkozos1($dhkozos1)
    {
        $this->dhkozos1 = $dhkozos1;
    
        return $this;
    }

    /**
     * Get dhkozos1
     *
     * @return integer 
     */
    public function getDhkozos1()
    {
        return $this->dhkozos1;
    }

    /**
     * Set dhkozos2
     *
     * @param integer $dhkozos2
     * @return Problems
     */
    public function setDhkozos2($dhkozos2)
    {
        $this->dhkozos2 = $dhkozos2;
    
        return $this;
    }

    /**
     * Get dhkozos2
     *
     * @return integer 
     */
    public function getDhkozos2()
    {
        return $this->dhkozos2;
    }

    /**
     * Set dhjvk3
     *
     * @param integer $dhjvk3
     * @return Problems
     */
    public function setDhjvk3($dhjvk3)
    {
        $this->dhjvk3 = $dhjvk3;
    
        return $this;
    }

    /**
     * Get dhjvk3
     *
     * @return integer 
     */
    public function getDhjvk3()
    {
        return $this->dhjvk3;
    }

    /**
     * Set dhelmu3
     *
     * @param integer $dhelmu3
     * @return Problems
     */
    public function setDhelmu3($dhelmu3)
    {
        $this->dhelmu3 = $dhelmu3;
    
        return $this;
    }

    /**
     * Get dhelmu3
     *
     * @return integer 
     */
    public function getDhelmu3()
    {
        return $this->dhelmu3;
    }

    /**
     * Set dhfogaz3
     *
     * @param integer $dhfogaz3
     * @return Problems
     */
    public function setDhfogaz3($dhfogaz3)
    {
        $this->dhfogaz3 = $dhfogaz3;
    
        return $this;
    }

    /**
     * Get dhfogaz3
     *
     * @return integer 
     */
    public function getDhfogaz3()
    {
        return $this->dhfogaz3;
    }

    /**
     * Set dhfotav3
     *
     * @param integer $dhfotav3
     * @return Problems
     */
    public function setDhfotav3($dhfotav3)
    {
        $this->dhfotav3 = $dhfotav3;
    
        return $this;
    }

    /**
     * Get dhfotav3
     *
     * @return integer 
     */
    public function getDhfotav3()
    {
        return $this->dhfotav3;
    }

    /**
     * Set dhdijbeszedo3
     *
     * @param integer $dhdijbeszedo3
     * @return Problems
     */
    public function setDhdijbeszedo3($dhdijbeszedo3)
    {
        $this->dhdijbeszedo3 = $dhdijbeszedo3;
    
        return $this;
    }

    /**
     * Get dhdijbeszedo3
     *
     * @return integer 
     */
    public function getDhdijbeszedo3()
    {
        return $this->dhdijbeszedo3;
    }

    /**
     * Set dhkozos3
     *
     * @param integer $dhkozos3
     * @return Problems
     */
    public function setDhkozos3($dhkozos3)
    {
        $this->dhkozos3 = $dhkozos3;
    
        return $this;
    }

    /**
     * Get dhkozos3
     *
     * @return integer 
     */
    public function getDhkozos3()
    {
        return $this->dhkozos3;
    }
}