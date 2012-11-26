<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Persons
 *
 * @ORM\Table(name="Persons")
 * @ORM\Entity
 */
class Persons
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Person_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $personId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Title", type="integer", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="Name1", type="text", nullable=true)
     */
    private $name1;

    /**
     * @var string
     *
     * @ORM\Column(name="Name2", type="text", nullable=true)
     */
    private $name2;

    /**
     * @var integer
     *
     * @ORM\Column(name="Type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="MobileNum", type="text", nullable=true)
     */
    private $mobilenum;

    /**
     * @var string
     *
     * @ORM\Column(name="PhoneNum", type="text", nullable=true)
     */
    private $phonenum;

    /**
     * @var string
     *
     * @ORM\Column(name="FaxNum", type="text", nullable=true)
     */
    private $faxnum;

    /**
     * @var string
     *
     * @ORM\Column(name="Email", type="text", nullable=true)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="Address_ID", type="integer", nullable=false)
     */
    private $addressId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Location_ID", type="integer", nullable=false)
     */
    private $locationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="MartialStatus", type="integer", nullable=true)
     */
    private $martialstatus;

    /**
     * @var integer
     *
     * @ORM\Column(name="EducationCode", type="integer", nullable=true)
     */
    private $educationcode;

    /**
     * @var string
     *
     * @ORM\Column(name="Note", type="text", nullable=true)
     */
    private $note;

    /**
     * @var float
     *
     * @ORM\Column(name="BirthDate", type="float", nullable=true)
     */
    private $birthdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="FamilySize", type="integer", nullable=true)
     */
    private $familysize;

    /**
     * @var integer
     *
     * @ORM\Column(name="EcActivity", type="integer", nullable=true)
     */
    private $ecactivity;

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
     * @ORM\Column(name="JobType", type="integer", nullable=true)
     */
    private $jobtype;

    /**
     * @var string
     *
     * @ORM\Column(name="MotherName1", type="text", nullable=true)
     */
    private $mothername1;

    /**
     * @var string
     *
     * @ORM\Column(name="MotherName2", type="text", nullable=true)
     */
    private $mothername2;

    /**
     * @var string
     *
     * @ORM\Column(name="ChildName1", type="text", nullable=true)
     */
    private $childname1;

    /**
     * @var string
     *
     * @ORM\Column(name="ChildName2", type="text", nullable=true)
     */
    private $childname2;

    /**
     * @var integer
     *
     * @ORM\Column(name="DelegateNeeded", type="integer", nullable=true)
     */
    private $delegateneeded;

    /**
     * @var string
     *
     * @ORM\Column(name="DelegateName1", type="text", nullable=true)
     */
    private $delegatename1;

    /**
     * @var string
     *
     * @ORM\Column(name="DelegateName2", type="text", nullable=true)
     */
    private $delegatename2;

    /**
     * @var integer
     *
     * @ORM\Column(name="FieldNum", type="integer", nullable=true)
     */
    private $fieldnum;

    /**
     * @var string
     *
     * @ORM\Column(name="FieldText", type="text", nullable=true)
     */
    private $fieldtext;

    /**
     * @var integer
     *
     * @ORM\Column(name="GenderType", type="integer", nullable=true)
     */
    private $gendertype;

    /**
     * @var string
     *
     * @ORM\Column(name="GazmuvekNum", type="text", nullable=true)
     */
    private $gazmuveknum;

    /**
     * @var string
     *
     * @ORM\Column(name="ElmuNum", type="text", nullable=true)
     */
    private $elmunum;

    /**
     * @var string
     *
     * @ORM\Column(name="JVKNum", type="text", nullable=true)
     */
    private $jvknum;

    /**
     * @var string
     *
     * @ORM\Column(name="FotavNum", type="text", nullable=true)
     */
    private $fotavnum;

    /**
     * @var string
     *
     * @ORM\Column(name="DijbeszedoNum", type="text", nullable=true)
     */
    private $dijbeszedonum;

    /**
     * @var string
     *
     * @ORM\Column(name="TarsAzonJel", type="text", nullable=true)
     */
    private $tarsazonjel;

    /**
     * @var string
     *
     * @ORM\Column(name="SzemSzam", type="text", nullable=true)
     */
    private $szemszam;

    /**
     * @var string
     *
     * @ORM\Column(name="SzemIgSzam", type="text", nullable=true)
     */
    private $szemigszam;

    /**
     * @var string
     *
     * @ORM\Column(name="BirthPlace", type="text", nullable=true)
     */
    private $birthplace;



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
     * Set title
     *
     * @param integer $title
     * @return Persons
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return integer 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set name1
     *
     * @param string $name1
     * @return Persons
     */
    public function setName1($name1)
    {
        $this->name1 = $name1;
    
        return $this;
    }

    /**
     * Get name1
     *
     * @return string 
     */
    public function getName1()
    {
        return $this->name1;
    }

    /**
     * Set name2
     *
     * @param string $name2
     * @return Persons
     */
    public function setName2($name2)
    {
        $this->name2 = $name2;
    
        return $this;
    }

    /**
     * Get name2
     *
     * @return string 
     */
    public function getName2()
    {
        return $this->name2;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Persons
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set mobilenum
     *
     * @param string $mobilenum
     * @return Persons
     */
    public function setMobilenum($mobilenum)
    {
        $this->mobilenum = $mobilenum;
    
        return $this;
    }

    /**
     * Get mobilenum
     *
     * @return string 
     */
    public function getMobilenum()
    {
        return $this->mobilenum;
    }

    /**
     * Set phonenum
     *
     * @param string $phonenum
     * @return Persons
     */
    public function setPhonenum($phonenum)
    {
        $this->phonenum = $phonenum;
    
        return $this;
    }

    /**
     * Get phonenum
     *
     * @return string 
     */
    public function getPhonenum()
    {
        return $this->phonenum;
    }

    /**
     * Set faxnum
     *
     * @param string $faxnum
     * @return Persons
     */
    public function setFaxnum($faxnum)
    {
        $this->faxnum = $faxnum;
    
        return $this;
    }

    /**
     * Get faxnum
     *
     * @return string 
     */
    public function getFaxnum()
    {
        return $this->faxnum;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Persons
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set addressId
     *
     * @param integer $addressId
     * @return Persons
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
     * Set locationId
     *
     * @param integer $locationId
     * @return Persons
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    
        return $this;
    }

    /**
     * Get locationId
     *
     * @return integer 
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Set martialstatus
     *
     * @param integer $martialstatus
     * @return Persons
     */
    public function setMartialstatus($martialstatus)
    {
        $this->martialstatus = $martialstatus;
    
        return $this;
    }

    /**
     * Get martialstatus
     *
     * @return integer 
     */
    public function getMartialstatus()
    {
        return $this->martialstatus;
    }

    /**
     * Set educationcode
     *
     * @param integer $educationcode
     * @return Persons
     */
    public function setEducationcode($educationcode)
    {
        $this->educationcode = $educationcode;
    
        return $this;
    }

    /**
     * Get educationcode
     *
     * @return integer 
     */
    public function getEducationcode()
    {
        return $this->educationcode;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Persons
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
     * Set birthdate
     *
     * @param float $birthdate
     * @return Persons
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    
        return $this;
    }

    /**
     * Get birthdate
     *
     * @return float 
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set familysize
     *
     * @param integer $familysize
     * @return Persons
     */
    public function setFamilysize($familysize)
    {
        $this->familysize = $familysize;
    
        return $this;
    }

    /**
     * Get familysize
     *
     * @return integer 
     */
    public function getFamilysize()
    {
        return $this->familysize;
    }

    /**
     * Set ecactivity
     *
     * @param integer $ecactivity
     * @return Persons
     */
    public function setEcactivity($ecactivity)
    {
        $this->ecactivity = $ecactivity;
    
        return $this;
    }

    /**
     * Get ecactivity
     *
     * @return integer 
     */
    public function getEcactivity()
    {
        return $this->ecactivity;
    }

    /**
     * Set createdon
     *
     * @param float $createdon
     * @return Persons
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
     * @return Persons
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
     * @return Persons
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
     * @return Persons
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
     * @return Persons
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
     * @return Persons
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
     * Set jobtype
     *
     * @param integer $jobtype
     * @return Persons
     */
    public function setJobtype($jobtype)
    {
        $this->jobtype = $jobtype;
    
        return $this;
    }

    /**
     * Get jobtype
     *
     * @return integer 
     */
    public function getJobtype()
    {
        return $this->jobtype;
    }

    /**
     * Set mothername1
     *
     * @param string $mothername1
     * @return Persons
     */
    public function setMothername1($mothername1)
    {
        $this->mothername1 = $mothername1;
    
        return $this;
    }

    /**
     * Get mothername1
     *
     * @return string 
     */
    public function getMothername1()
    {
        return $this->mothername1;
    }

    /**
     * Set mothername2
     *
     * @param string $mothername2
     * @return Persons
     */
    public function setMothername2($mothername2)
    {
        $this->mothername2 = $mothername2;
    
        return $this;
    }

    /**
     * Get mothername2
     *
     * @return string 
     */
    public function getMothername2()
    {
        return $this->mothername2;
    }

    /**
     * Set childname1
     *
     * @param string $childname1
     * @return Persons
     */
    public function setChildname1($childname1)
    {
        $this->childname1 = $childname1;
    
        return $this;
    }

    /**
     * Get childname1
     *
     * @return string 
     */
    public function getChildname1()
    {
        return $this->childname1;
    }

    /**
     * Set childname2
     *
     * @param string $childname2
     * @return Persons
     */
    public function setChildname2($childname2)
    {
        $this->childname2 = $childname2;
    
        return $this;
    }

    /**
     * Get childname2
     *
     * @return string 
     */
    public function getChildname2()
    {
        return $this->childname2;
    }

    /**
     * Set delegateneeded
     *
     * @param integer $delegateneeded
     * @return Persons
     */
    public function setDelegateneeded($delegateneeded)
    {
        $this->delegateneeded = $delegateneeded;
    
        return $this;
    }

    /**
     * Get delegateneeded
     *
     * @return integer 
     */
    public function getDelegateneeded()
    {
        return $this->delegateneeded;
    }

    /**
     * Set delegatename1
     *
     * @param string $delegatename1
     * @return Persons
     */
    public function setDelegatename1($delegatename1)
    {
        $this->delegatename1 = $delegatename1;
    
        return $this;
    }

    /**
     * Get delegatename1
     *
     * @return string 
     */
    public function getDelegatename1()
    {
        return $this->delegatename1;
    }

    /**
     * Set delegatename2
     *
     * @param string $delegatename2
     * @return Persons
     */
    public function setDelegatename2($delegatename2)
    {
        $this->delegatename2 = $delegatename2;
    
        return $this;
    }

    /**
     * Get delegatename2
     *
     * @return string 
     */
    public function getDelegatename2()
    {
        return $this->delegatename2;
    }

    /**
     * Set fieldnum
     *
     * @param integer $fieldnum
     * @return Persons
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
     * Set fieldtext
     *
     * @param string $fieldtext
     * @return Persons
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
     * Set gendertype
     *
     * @param integer $gendertype
     * @return Persons
     */
    public function setGendertype($gendertype)
    {
        $this->gendertype = $gendertype;
    
        return $this;
    }

    /**
     * Get gendertype
     *
     * @return integer 
     */
    public function getGendertype()
    {
        return $this->gendertype;
    }

    /**
     * Set gazmuveknum
     *
     * @param string $gazmuveknum
     * @return Persons
     */
    public function setGazmuveknum($gazmuveknum)
    {
        $this->gazmuveknum = $gazmuveknum;
    
        return $this;
    }

    /**
     * Get gazmuveknum
     *
     * @return string 
     */
    public function getGazmuveknum()
    {
        return $this->gazmuveknum;
    }

    /**
     * Set elmunum
     *
     * @param string $elmunum
     * @return Persons
     */
    public function setElmunum($elmunum)
    {
        $this->elmunum = $elmunum;
    
        return $this;
    }

    /**
     * Get elmunum
     *
     * @return string 
     */
    public function getElmunum()
    {
        return $this->elmunum;
    }

    /**
     * Set jvknum
     *
     * @param string $jvknum
     * @return Persons
     */
    public function setJvknum($jvknum)
    {
        $this->jvknum = $jvknum;
    
        return $this;
    }

    /**
     * Get jvknum
     *
     * @return string 
     */
    public function getJvknum()
    {
        return $this->jvknum;
    }

    /**
     * Set fotavnum
     *
     * @param string $fotavnum
     * @return Persons
     */
    public function setFotavnum($fotavnum)
    {
        $this->fotavnum = $fotavnum;
    
        return $this;
    }

    /**
     * Get fotavnum
     *
     * @return string 
     */
    public function getFotavnum()
    {
        return $this->fotavnum;
    }

    /**
     * Set dijbeszedonum
     *
     * @param string $dijbeszedonum
     * @return Persons
     */
    public function setDijbeszedonum($dijbeszedonum)
    {
        $this->dijbeszedonum = $dijbeszedonum;
    
        return $this;
    }

    /**
     * Get dijbeszedonum
     *
     * @return string 
     */
    public function getDijbeszedonum()
    {
        return $this->dijbeszedonum;
    }

    /**
     * Set tarsazonjel
     *
     * @param string $tarsazonjel
     * @return Persons
     */
    public function setTarsazonjel($tarsazonjel)
    {
        $this->tarsazonjel = $tarsazonjel;
    
        return $this;
    }

    /**
     * Get tarsazonjel
     *
     * @return string 
     */
    public function getTarsazonjel()
    {
        return $this->tarsazonjel;
    }

    /**
     * Set szemszam
     *
     * @param string $szemszam
     * @return Persons
     */
    public function setSzemszam($szemszam)
    {
        $this->szemszam = $szemszam;
    
        return $this;
    }

    /**
     * Get szemszam
     *
     * @return string 
     */
    public function getSzemszam()
    {
        return $this->szemszam;
    }

    /**
     * Set szemigszam
     *
     * @param string $szemigszam
     * @return Persons
     */
    public function setSzemigszam($szemigszam)
    {
        $this->szemigszam = $szemigszam;
    
        return $this;
    }

    /**
     * Get szemigszam
     *
     * @return string 
     */
    public function getSzemigszam()
    {
        return $this->szemigszam;
    }

    /**
     * Set birthplace
     *
     * @param string $birthplace
     * @return Persons
     */
    public function setBirthplace($birthplace)
    {
        $this->birthplace = $birthplace;
    
        return $this;
    }

    /**
     * Get birthplace
     *
     * @return string 
     */
    public function getBirthplace()
    {
        return $this->birthplace;
    }
}