<?php

namespace JCSGYK\DbimportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Client
 *
 * @ORM\Table(name="client")
 * @ORM\Entity
 */
class Client
{
    /** Family help client type */
    const FH = 1;
    /** Child welfare client type */
    const CW = 2;
    /** Parent type */
    const PARENT = 3;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", nullable=true)
     */
    private $companyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="case_year", type="integer", nullable=true)
     */
    private $caseYear;

    /**
     * @var integer
     *
     * @ORM\Column(name="case_number", type="integer", nullable=true)
     */
    private $caseNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="case_label", type="string", length=20, nullable=true)
     */
    private $caseLabel;

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
     * @var integer
     *
     * @ORM\Column(name="gender", type="integer", nullable=true)
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
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=60, nullable=true)
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="zip_code", type="string", length=10, nullable=true)
     */
    private $zipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="street", type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(name="street_type", type="string", length=16, nullable=true)
     */
    private $streetType;

    /**
     * @var string
     *
     * @ORM\Column(name="street_number", type="string", length=16, nullable=true)
     */
    private $streetNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="flat_number", type="string", length=16, nullable=true)
     */
    private $flatNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="location_country", type="string", length=60, nullable=true)
     */
    private $locationCountry;

    /**
     * @var string
     *
     * @ORM\Column(name="location_zip_code", type="string", length=10, nullable=true)
     */
    private $locationZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="location_city", type="string", length=255, nullable=true)
     */
    private $locationCity;

    /**
     * @var string
     *
     * @ORM\Column(name="location_street", type="string", length=255, nullable=true)
     */
    private $locationStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="location_street_type", type="string", length=16, nullable=true)
     */
    private $locationStreetType;

    /**
     * @var string
     *
     * @ORM\Column(name="location_street_number", type="string", length=16, nullable=true)
     */
    private $locationStreetNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="location_flat_number", type="string", length=16, nullable=true)
     */
    private $locationFlatNumber;

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
     * @var string
     *
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    private $note;

    /**
     * @var integer
     *
     * @ORM\Column(name="case_admin", type="integer", nullable=true)
     */
    private $caseAdmin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", nullable=true)
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
     * @ORM\Column(name="modified_by", type="integer", nullable=true)
     */
    private $modifiedBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="opened_by", type="integer", nullable=true)
     */
    private $openedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="doc_file", type="text", nullable=true)
     */
    private $docFile;

    /**
     * @var string
     *
     * @ORM\Column(name="guardian_firstname", type="string", length=255, nullable=true)
     */
    private $guardianFirstname;

    /**
     * @var string
     *
     * @ORM\Column(name="guardian_lastname", type="string", length=255, nullable=true)
     */
    private $guardianLastname;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_archived", type="integer", nullable=true)
     */
    private $isArchived;

    /**
     * @ORM\OneToMany(targetEntity="UtilityproviderClientnumber", mappedBy="client")
     */
    private $utilityproviders;

   /**
     * @var \Date
     *
     * @ORM\Column(name="agreement_expires_at", type="date", nullable=true)
     */
    private $agreementExpiresAt;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    private $parameters;

    public function __construct()
    {
        $this->utilityproviders = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return Client
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
     * Set companyId
     *
     * @param integer $companyId
     * @return Client
     */
    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @param integer $gender
     * @return Client
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return integer
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set birthDate
     *
     * @param \DateTime $birthDate
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
     */
    public function setSocialSecurityNumber($socialSecurityNumber)
    {
        $this->socialSecurityNumber = $this::cleanupNum($socialSecurityNumber);

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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * Set country
     *
     * @param string $country
     * @return Client
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     * @return Client
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Client
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set street
     *
     * @param string $street
     * @return Client
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set streetType
     *
     * @param string $streetType
     * @return Client
     */
    public function setStreetType($streetType)
    {
        $this->streetType = $streetType;

        return $this;
    }

    /**
     * Get streetType
     *
     * @return string
     */
    public function getStreetType()
    {
        return $this->streetType;
    }

    /**
     * Set streetNumber
     *
     * @param string $streetNumber
     * @return Client
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * Get streetNumber
     *
     * @return string
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * Set flatNumber
     *
     * @param string $flatNumber
     * @return Client
     */
    public function setFlatNumber($flatNumber)
    {
        $this->flatNumber = $flatNumber;

        return $this;
    }

    /**
     * Get flatNumber
     *
     * @return string
     */
    public function getFlatNumber()
    {
        return $this->flatNumber;
    }

    /**
     * Set locationCountry
     *
     * @param string $locationCountry
     * @return Client
     */
    public function setLocationCountry($locationCountry)
    {
        $this->locationCountry = $locationCountry;

        return $this;
    }

    /**
     * Get locationCountry
     *
     * @return string
     */
    public function getLocationCountry()
    {
        return $this->locationCountry;
    }

    /**
     * Set locationZipCode
     *
     * @param string $locationZipCode
     * @return Client
     */
    public function setLocationZipCode($locationZipCode)
    {
        $this->locationZipCode = $locationZipCode;

        return $this;
    }

    /**
     * Get locationZipCode
     *
     * @return string
     */
    public function getLocationZipCode()
    {
        return $this->locationZipCode;
    }

    /**
     * Set locationCity
     *
     * @param string $locationCity
     * @return Client
     */
    public function setLocationCity($locationCity)
    {
        $this->locationCity = $locationCity;

        return $this;
    }

    /**
     * Get locationCity
     *
     * @return string
     */
    public function getLocationCity()
    {
        return $this->locationCity;
    }

    /**
     * Set locationStreet
     *
     * @param string $locationStreet
     * @return Client
     */
    public function setLocationStreet($locationStreet)
    {
        $this->locationStreet = $locationStreet;

        return $this;
    }

    /**
     * Get locationStreet
     *
     * @return string
     */
    public function getLocationStreet()
    {
        return $this->locationStreet;
    }

    /**
     * Set locationStreetType
     *
     * @param string $locationStreetType
     * @return Client
     */
    public function setLocationStreetType($locationStreetType)
    {
        $this->locationStreetType = $locationStreetType;

        return $this;
    }

    /**
     * Get locationStreetType
     *
     * @return string
     */
    public function getLocationStreetType()
    {
        return $this->locationStreetType;
    }

    /**
     * Set locationStreetNumber
     *
     * @param string $locationStreetNumber
     * @return Client
     */
    public function setLocationStreetNumber($locationStreetNumber)
    {
        $this->locationStreetNumber = $locationStreetNumber;

        return $this;
    }

    /**
     * Get locationStreetNumber
     *
     * @return string
     */
    public function getLocationStreetNumber()
    {
        return $this->locationStreetNumber;
    }

    /**
     * Set locationFlatNumber
     *
     * @param string $locationFlatNumber
     * @return Client
     */
    public function setLocationFlatNumber($locationFlatNumber)
    {
        $this->locationFlatNumber = $locationFlatNumber;

        return $this;
    }

    /**
     * Get locationFlatNumber
     *
     * @return string
     */
    public function getLocationFlatNumber()
    {
        return $this->locationFlatNumber;
    }

    /**
     * Set citizenshipStatus
     *
     * @param integer $citizenshipStatus
     * @return Client
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
     * @return Client
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
     * Set note
     *
     * @param string $note
     * @return Client
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * @return Client
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
     * Set guardianFirstname
     *
     * @param string $guardianFirstname
     * @return Client
     */
    public function setGuardianFirstname($guardianFirstname)
    {
        $this->guardianFirstname = $guardianFirstname;

        return $this;
    }

    /**
     * Get guardianFirstname
     *
     * @return string
     */
    public function getGuardianFirstname()
    {
        return $this->guardianFirstname;
    }

    /**
     * Set guardianLastname
     *
     * @param string $guardianLastname
     * @return Client
     */
    public function setGuardianLastname($guardianLastname)
    {
        $this->guardianLastname = $guardianLastname;

        return $this;
    }

    /**
     * Get guardianLastname
     *
     * @return string
     */
    public function getGuardianLastname()
    {
        return $this->guardianLastname;
    }

    /**
     * Add utilityproviders
     *
     * @param \JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders
     * @return Client
     */
    public function addUtilityproviderid(\JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders)
    {
        $this->utilityproviders[] = $utilityproviders;

        return $this;
    }

    /**
     * Remove utilityproviders
     *
     * @param \JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders
     */
    public function removeUtilityproviderid(\JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders)
    {
        $this->utilityproviderids->removeElement($utilityproviders);
    }

    /**
     * Get utilityproviders
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUtilityproviders()
    {
        return $this->utilityproviders;
    }

    /**
     * Add utilityproviders
     *
     * @param \JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders
     * @return Client
     */
    public function addUtilityprovider(\JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders)
    {
        $this->utilityproviders[] = $utilityproviders;

        return $this;
    }

    /**
     * Remove utilityproviders
     *
     * @param \JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders
     */
    public function removeUtilityprovider(\JCSGYK\DbimportBundle\Entity\Utilityprovider $utilityproviders)
    {
        $this->utilityproviders->removeElement($utilityproviders);
    }



    /**
     * Set caseAdmin
     *
     * @param integer $caseAdmin
     * @return Client
     */
    public function setCaseAdmin($caseAdmin)
    {
        $this->caseAdmin = $caseAdmin;

        return $this;
    }

    /**
     * Get caseAdmin
     *
     * @return integer
     */
    public function getCaseAdmin()
    {
        return $this->caseAdmin;
    }

    /**
     * Set isArchived
     *
     * @param integer $isArchived
     * @return Client
     */
    public function setIsArchived($isArchived)
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    /**
     * Get isArchived
     *
     * @return integer
     */
    public function getIsArchived()
    {
        return $this->isArchived;
    }

    /**
     * Set agreementExpiresAt
     *
     * @param \DateTime $agreementExpiresAt
     * @return Problem
     */
    public function setAgreementExpiresAt($agreementExpiresAt)
    {
        $this->agreementExpiresAt = $agreementExpiresAt;

        return $this;
    }

    /**
     * Get agreementExpiresAt
     *
     * @return \DateTime
     */
    public function getAgreementExpiresAt()
    {
        return $this->agreementExpiresAt;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Client
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
     * Set caseYear
     *
     * @param integer $caseYear
     * @return Client
     */
    public function setCaseYear($caseYear)
    {
        $this->caseYear = $caseYear;

        return $this;
    }

    /**
     * Get caseYear
     *
     * @return integer
     */
    public function getCaseYear()
    {
        return $this->caseYear;
    }

    /**
     * Set caseNumber
     *
     * @param integer $caseNumber
     * @return Client
     */
    public function setCaseNumber($caseNumber)
    {
        $this->caseNumber = $caseNumber;

        return $this;
    }

    /**
     * Get caseNumber
     *
     * @return integer
     */
    public function getCaseNumber()
    {
        return $this->caseNumber;
    }


    /**
     * Set caseLabel
     *
     * @param string $caseLabel
     * @return Client
     */
    public function setCaseLabel($caseLabel)
    {
        $this->caseLabel = $caseLabel;

        return $this;
    }

    /**
     * Get caseLabel
     *
     * @return string
     */
    public function getCaseLabel()
    {
        return $this->caseLabel;
    }

    /**
     * Get parameters
     *
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Json encode and set parameters
     *
     * @param array $parameters
     * @return Client
     */
    public function setParams($parameters)
    {
        $this->parameters = json_encode($parameters);

        return $this;
    }

    /**
     * Json decode and get parameters
     *
     * @return array
     */
    public function getParams()
    {
        return json_decode($this->parameters, true);
    }

    /**
     * Return a value of the parameters array
     * @param int $groupid
     * @return mixed param value
     */
    public function getParam($groupid)
    {
        $plist = $this->getParams();

        return isset($plist[$groupid]) ? $plist[$groupid] : null;
    }

    /**
     * Removes - . _ and spaces from the input. Used by Social security numbers
     * @param string $in
     * @return string
     */
    public static function cleanupNum($in)
    {
        return strtr(trim($in), ['-' => '', '.' => '', ' ' => '', '_' => '']);
    }
}