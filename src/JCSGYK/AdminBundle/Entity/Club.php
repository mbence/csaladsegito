<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Club
 *
 * @ORM\Table(name="club")
 * @ORM\Entity(repositoryClass="JCSGYK\AdminBundle\Entity\ClubRepository")
 */
class Club
{
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
     * @ORM\Column(name="company_id", type="integer", nullable=false)
     */
    private $companyId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=100, nullable=true)
     */
    private $phone;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="clubCoordinator", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $coordinator;

    /**
     * @var string
     *
     * @ORM\Column(name="lunch_types", type="text", nullable=true)
     */
    private $lunchTypes;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @ORM\OneToMany(targetEntity="Catering", mappedBy="club")
     */
    private $clientcaterings;

    public function __construct($salt = null)
    {
        $this->clientcaterings = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
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
     * @return Club
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
     * Set name
     *
     * @param string $name
     * @return Club
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Club
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Club
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
     * Set coordinator
     *
     * @param \JCSGYK\AdminBundle\Entity\User $coordinator
     * @return Club
     */
    public function setCoordinator(\JCSGYK\AdminBundle\Entity\User $coordinator = null)
    {
        $this->coordinator = $coordinator;

        return $this;
    }

    /**
     * Get coordinator
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getCoordinator()
    {
        return $this->coordinator;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Company
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add clientcaterings
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $clientcaterings
     *
     * @return Club
     */
    public function addClientcatering(\JCSGYK\AdminBundle\Entity\Catering $clientcaterings)
    {
        $this->clientcaterings[] = $clientcaterings;

        return $this;
    }

    /**
     * Remove clientcaterings
     *
     * @param \JCSGYK\AdminBundle\Entity\Catering $clientcaterings
     */
    public function removeClientcatering(\JCSGYK\AdminBundle\Entity\Catering $clientcaterings)
    {
        $this->clientcaterings->removeElement($clientcaterings);
    }

    /**
     * Get clientcaterings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClientcaterings()
    {
        return $this->clientcaterings;
    }

    /**
     * Set lunchTypes
     *
     * @param string $lunchTypes
     *
     * @return Club
     */
    public function setLunchTypes($lunchTypes)
    {
        $this->lunchTypes = json_encode($lunchTypes);

        return $this;
    }

    /**
     * Get lunchTypes
     *
     * @return string
     */
    public function getLunchTypes()
    {
        return json_decode($this->lunchTypes, true);
    }
}
