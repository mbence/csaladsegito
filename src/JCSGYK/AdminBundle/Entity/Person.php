<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Person
 *
 * @ORM\Table(name="person")
 * @ORM\Entity
 */
class Person
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     */
//     * @ORM\GeneratedValue(strategy="IDENTITY")
    
    private $id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=10, nullable=true)
     */
    private $title;
    
    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=false)
     */
    private $lastname;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gender", type="boolean", nullable=true)
     */
    private $gender;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birth_date", type="date", nullable=true)
     */
    private $birthDate;

    /**
     * @var string
     *
     * @ORM\Column(name="birth_place", type="string", length=64, nullable=true)
     */
    private $birthPlace;

    /**
     * @var string
     *
     * @ORM\Column(name="birth_title", type="string", length=10, nullable=true)
     */
    private $birthTitle;
    
    /**
     * @var string
     *
     * @ORM\Column(name="birth_firstname", type="string", length=255, nullable=true)
     */
    private $birthFirstname;
    
    /**
     * @var string
     *
     * @ORM\Column(name="birth_lastname", type="string", length=255, nullable=true)
     */
    private $birthLastname;

    /**
     * @var string
     *
     * @ORM\Column(name="mother_title", type="string", length=10, nullable=true)
     */
    private $motherTitle;
    
    /**
     * @var string
     *
     * @ORM\Column(name="mother_firstname", type="string", length=255, nullable=true)
     */
    private $motherFirstname;
    
    /**
     * @var string
     *
     * @ORM\Column(name="mother_lastname", type="string", length=255, nullable=true)
     */
    private $motherLastname;
    /**
     * @var string
     *
     * @ORM\Column(name="social_security_number", type="string", length=16, nullable=true)
     */
    private $socialSecurityNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_number", type="string", length=16, nullable=true)
     */
    private $identityNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="id_card_number", type="string", length=16, nullable=true)
     */
    private $idCardNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=32, nullable=true)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=32, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=32, nullable=true)
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=128, nullable=true)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="address_id", type="integer", nullable=false)
     */
    private $addressId;

    /**
     * @var integer
     *
     * @ORM\Column(name="location_id", type="integer", nullable=false)
     */
    private $locationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="martial_status", type="integer", nullable=true)
     */
    private $martialStatus;

    /**
     * @var integer
     *
     * @ORM\Column(name="citizenship_status", type="integer", nullable=true)
     */
    private $citizenshipStatus;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="citizenship", type="integer", nullable=true)
     */
    private $citizenship;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="education_code", type="integer", nullable=true)
     */
    private $educationCode;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    private $note;

    /**
     * @var integer
     *
     * @ORM\Column(name="family_size", type="integer", nullable=true)
     */
    private $familySize;

    /**
     * @var integer
     *
     * @ORM\Column(name="ec_activity", type="integer", nullable=true)
     */
    private $ecActivity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="modified_by", type="integer", nullable=false)
     */
    private $modifiedBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="opened_by", type="integer", nullable=false)
     */
    private $openedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="doc_file", type="text", nullable=true)
     */
    private $docFile;

    /**
     * @var integer
     *
     * @ORM\Column(name="job_type", type="integer", nullable=true)
     */
    private $jobType;

    /**
     * @var integer
     *
     * @ORM\Column(name="delegate_needed", type="integer", nullable=true)
     */
    private $delegateNeeded;

    /**
     * @var string
     *
     * @ORM\Column(name="delegate_name", type="string", length=255, nullable=true)
     */
    private $delegateName;


    /**
     * Set id
     *
     * @param integer $id
     * @return Person
     */
    public function setId($id)
    {
        $this->id = $id;
    
        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Person
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
     * Set firstname
     *
     * @param string $firstname
     * @return Person
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    
        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Person
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    
        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set gender
     *
     * @param boolean $gender
     * @return Person
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    
        return $this;
    }

    /**
     * Get gender
     *
     * @return boolean 
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set birthDate
     *
     * @param \DateTime $birthDate
     * @return Person
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;
    
        return $this;
    }

    /**
     * Get birthDate
     *
     * @return \DateTime 
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * Set birthPlace
     *
     * @param string $birthPlace
     * @return Person
     */
    public function setBirthPlace($birthPlace)
    {
        $this->birthPlace = $birthPlace;
    
        return $this;
    }

    /**
     * Get birthPlace
     *
     * @return string 
     */
    public function getBirthPlace()
    {
        return $this->birthPlace;
    }

    /**
     * Set birthTitle
     *
     * @param string $birthTitle
     * @return Person
     */
    public function setBirthTitle($birthTitle)
    {
        $this->birthTitle = $birthTitle;
    
        return $this;
    }

    /**
     * Get birthTitle
     *
     * @return string 
     */
    public function getBirthTitle()
    {
        return $this->birthTitle;
    }

    /**
     * Set birthFirstname
     *
     * @param string $birthFirstname
     * @return Person
     */
    public function setBirthFirstname($birthFirstname)
    {
        $this->birthFirstname = $birthFirstname;
    
        return $this;
    }

    /**
     * Get birthFirstname
     *
     * @return string 
     */
    public function getBirthFirstname()
    {
        return $this->birthFirstname;
    }

    /**
     * Set birthLastname
     *
     * @param string $birthLastname
     * @return Person
     */
    public function setBirthLastname($birthLastname)
    {
        $this->birthLastname = $birthLastname;
    
        return $this;
    }

    /**
     * Get birthLastname
     *
     * @return string 
     */
    public function getBirthLastname()
    {
        return $this->birthLastname;
    }

    /**
     * Set motherTitle
     *
     * @param string $motherTitle
     * @return Person
     */
    public function setMotherTitle($motherTitle)
    {
        $this->motherTitle = $motherTitle;
    
        return $this;
    }

    /**
     * Get motherTitle
     *
     * @return string 
     */
    public function getMotherTitle()
    {
        return $this->motherTitle;
    }

    /**
     * Set motherFirstname
     *
     * @param string $motherFirstname
     * @return Person
     */
    public function setMotherFirstname($motherFirstname)
    {
        $this->motherFirstname = $motherFirstname;
    
        return $this;
    }

    /**
     * Get motherFirstname
     *
     * @return string 
     */
    public function getMotherFirstname()
    {
        return $this->motherFirstname;
    }

    /**
     * Set motherLastname
     *
     * @param string $motherLastname
     * @return Person
     */
    public function setMotherLastname($motherLastname)
    {
        $this->motherLastname = $motherLastname;
    
        return $this;
    }

    /**
     * Get motherLastname
     *
     * @return string 
     */
    public function getMotherLastname()
    {
        return $this->motherLastname;
    }

    /**
     * Set socialSecurityNumber
     *
     * @param string $socialSecurityNumber
     * @return Person
     */
    public function setSocialSecurityNumber($socialSecurityNumber)
    {
        $this->socialSecurityNumber = $socialSecurityNumber;
    
        return $this;
    }

    /**
     * Get socialSecurityNumber
     *
     * @return string 
     */
    public function getSocialSecurityNumber()
    {
        return $this->socialSecurityNumber;
    }

    /**
     * Set identityNumber
     *
     * @param string $identityNumber
     * @return Person
     */
    public function setIdentityNumber($identityNumber)
    {
        $this->identityNumber = $identityNumber;
    
        return $this;
    }

    /**
     * Get identityNumber
     *
     * @return string 
     */
    public function getIdentityNumber()
    {
        return $this->identityNumber;
    }

    /**
     * Set idCardNumber
     *
     * @param string $idCardNumber
     * @return Person
     */
    public function setIdCardNumber($idCardNumber)
    {
        $this->idCardNumber = $idCardNumber;
    
        return $this;
    }

    /**
     * Get idCardNumber
     *
     * @return string 
     */
    public function getIdCardNumber()
    {
        return $this->idCardNumber;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     * @return Person
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
    
        return $this;
    }

    /**
     * Get mobile
     *
     * @return string 
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Person
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set fax
     *
     * @param string $fax
     * @return Person
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    
        return $this;
    }

    /**
     * Get fax
     *
     * @return string 
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Person
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
     * @return Person
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
     * @return Person
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
     * Set martialStatus
     *
     * @param integer $martialStatus
     * @return Person
     */
    public function setMartialStatus($martialStatus)
    {
        $this->martialStatus = $martialStatus;
    
        return $this;
    }

    /**
     * Get martialStatus
     *
     * @return integer 
     */
    public function getMartialStatus()
    {
        return $this->martialStatus;
    }

    /**
     * Set citizenshipStatus
     *
     * @param integer $citizenshipStatus
     * @return Person
     */
    public function setCitizenshipStatus($citizenshipStatus)
    {
        $this->citizenshipStatus = $citizenshipStatus;
    
        return $this;
    }

    /**
     * Get citizenshipStatus
     *
     * @return integer 
     */
    public function getCitizenshipStatus()
    {
        return $this->citizenshipStatus;
    }

    /**
     * Set citizenship
     *
     * @param integer $citizenship
     * @return Person
     */
    public function setCitizenship($citizenship)
    {
        $this->citizenship = $citizenship;
    
        return $this;
    }

    /**
     * Get citizenship
     *
     * @return integer 
     */
    public function getCitizenship()
    {
        return $this->citizenship;
    }

    /**
     * Set educationCode
     *
     * @param integer $educationCode
     * @return Person
     */
    public function setEducationCode($educationCode)
    {
        $this->educationCode = $educationCode;
    
        return $this;
    }

    /**
     * Get educationCode
     *
     * @return integer 
     */
    public function getEducationCode()
    {
        return $this->educationCode;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Person
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
     * Set familySize
     *
     * @param integer $familySize
     * @return Person
     */
    public function setFamilySize($familySize)
    {
        $this->familySize = $familySize;
    
        return $this;
    }

    /**
     * Get familySize
     *
     * @return integer 
     */
    public function getFamilySize()
    {
        return $this->familySize;
    }

    /**
     * Set ecActivity
     *
     * @param integer $ecActivity
     * @return Person
     */
    public function setEcActivity($ecActivity)
    {
        $this->ecActivity = $ecActivity;
    
        return $this;
    }

    /**
     * Get ecActivity
     *
     * @return integer 
     */
    public function getEcActivity()
    {
        return $this->ecActivity;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Person
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Person
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    
        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     * @return Person
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;
    
        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime 
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     * @return Person
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;
    
        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return integer 
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set openedBy
     *
     * @param integer $openedBy
     * @return Person
     */
    public function setOpenedBy($openedBy)
    {
        $this->openedBy = $openedBy;
    
        return $this;
    }

    /**
     * Get openedBy
     *
     * @return integer 
     */
    public function getOpenedBy()
    {
        return $this->openedBy;
    }

    /**
     * Set docFile
     *
     * @param string $docFile
     * @return Person
     */
    public function setDocFile($docFile)
    {
        $this->docFile = $docFile;
    
        return $this;
    }

    /**
     * Get docFile
     *
     * @return string 
     */
    public function getDocFile()
    {
        return $this->docFile;
    }

    /**
     * Set jobType
     *
     * @param integer $jobType
     * @return Person
     */
    public function setJobType($jobType)
    {
        $this->jobType = $jobType;
    
        return $this;
    }

    /**
     * Get jobType
     *
     * @return integer 
     */
    public function getJobType()
    {
        return $this->jobType;
    }

    /**
     * Set delegateNeeded
     *
     * @param integer $delegateNeeded
     * @return Person
     */
    public function setDelegateNeeded($delegateNeeded)
    {
        $this->delegateNeeded = $delegateNeeded;
    
        return $this;
    }

    /**
     * Get delegateNeeded
     *
     * @return integer 
     */
    public function getDelegateNeeded()
    {
        return $this->delegateNeeded;
    }

    /**
     * Set delegateName
     *
     * @param string $delegateName
     * @return Person
     */
    public function setDelegateName($delegateName)
    {
        $this->delegateName = $delegateName;
    
        return $this;
    }

    /**
     * Get delegateName
     *
     * @return string 
     */
    public function getDelegateName()
    {
        return $this->delegateName;
    }
}