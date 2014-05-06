<?php

namespace JCSGYK\AdminBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="admin_user")
 * @ORM\Entity(repositoryClass="JCSGYK\AdminBundle\Entity\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", nullable=true)
     */
    private $companyId;

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
     * @ORM\OneToMany(targetEntity="Client", mappedBy="caseAdmin")
     */
    private $clientCaseAdmin;

    /**
     * @ORM\OneToMany(targetEntity="Client", mappedBy="creator")
     */
    private $clientcreated;

    /**
     * @ORM\OneToMany(targetEntity="Client", mappedBy="modifier")
     */
    private $clientmodified;

    /**
     * @ORM\OneToMany(targetEntity="Archive", mappedBy="creator")
     */
    private $archivecreated;

    /**
     * @ORM\OneToMany(targetEntity="Task", mappedBy="assignee")
     */
    private $tasks;

    /**
     * @ORM\OneToMany(targetEntity="Club", mappedBy="coordinator")
     */
    private $clubCoordinator;

    /**
     * @ORM\OneToMany(targetEntity="MonthlyClosing", mappedBy="creator")
     */
    private $closings;

    /**
     * @ORM\OneToMany(targetEntity="LunchOrder", mappedBy="creator")
     */
    private $lunchorders;

    /**
     * @ORM\OneToMany(targetEntity="Option", mappedBy="creator")
     */
    private $optioncreated;

    /**
     * @ORM\OneToMany(targetEntity="Option", mappedBy="modifier")
     */
    private $optionmodified;


    public function __construct($salt = null)
    {
        parent::__construct();
        // your own logic
        if (!is_null($salt)) {
            $this->salt = $salt;
        }

        $this->clientCaseAdmin = new ArrayCollection();
        $this->clientcreated = new ArrayCollection();
        $this->clientmodified = new ArrayCollection();
        $this->archivecreated = new ArrayCollection();
        $this->clubCoordinator = new ArrayCollection();
        $this->closings = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->lunchorders = new ArrayCollection();
        $this->optioncreated = new ArrayCollection();
        $this->optionmodified = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getLastname() . ' ' . $this->getFirstname() . ($this->enabled ? '' : ' (inaktív)');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('firstname', new Assert\NotBlank);
        $metadata->addPropertyConstraint('lastname', new Assert\NotBlank);
        $metadata->addPropertyConstraint('username', new Assert\NotBlank);
        $metadata->addPropertyConstraint('email', new Assert\Email);
        $metadata->addPropertyConstraint('roles', new Assert\NotBlank);
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
     * @return User
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
     * Add clientCaseAdmin
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $clientCaseAdmin
     * @return User
     */
    public function addClientCaseAdmin(\JCSGYK\AdminBundle\Entity\Client $clientCaseAdmin)
    {
        $this->clientCaseAdmin[] = $clientCaseAdmin;

        return $this;
    }

    /**
     * Remove clientCaseAdmin
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $clientCaseAdmin
     */
    public function removeClientCaseAdmin(\JCSGYK\AdminBundle\Entity\Client $clientCaseAdmin)
    {
        $this->clientCaseAdmin->removeElement($clientCaseAdmin);
    }

    /**
     * Get clientCaseAdmin
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClientCaseAdmin()
    {
        return $this->clientCaseAdmin;
    }

    /**
     * Add clientcreated
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $clientcreated
     * @return User
     */
    public function addClientcreated(\JCSGYK\AdminBundle\Entity\Client $clientcreated)
    {
        $this->clientcreated[] = $clientcreated;

        return $this;
    }

    /**
     * Remove clientcreated
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $clientcreated
     */
    public function removeClientcreated(\JCSGYK\AdminBundle\Entity\Client $clientcreated)
    {
        $this->clientcreated->removeElement($clientcreated);
    }

    /**
     * Get clientcreated
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClientcreated()
    {
        return $this->clientcreated;
    }

    /**
     * Add clientmodified
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $clientmodified
     * @return User
     */
    public function addClientmodified(\JCSGYK\AdminBundle\Entity\Client $clientmodified)
    {
        $this->clientmodified[] = $clientmodified;

        return $this;
    }

    /**
     * Remove clientmodified
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $clientmodified
     */
    public function removeClientmodified(\JCSGYK\AdminBundle\Entity\Client $clientmodified)
    {
        $this->clientmodified->removeElement($clientmodified);
    }

    /**
     * Get clientmodified
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClientmodified()
    {
        return $this->clientmodified;
    }

    /**
     * Add archivecreated
     *
     * @param \JCSGYK\AdminBundle\Entity\Archive $archivecreated
     * @return User
     */
    public function addArchivecreated(\JCSGYK\AdminBundle\Entity\Archive $archivecreated)
    {
        $this->archivecreated[] = $archivecreated;

        return $this;
    }

    /**
     * Remove archivecreated
     *
     * @param \JCSGYK\AdminBundle\Entity\Archive $archivecreated
     */
    public function removeArchivecreated(\JCSGYK\AdminBundle\Entity\Archive $archivecreated)
    {
        $this->archivecreated->removeElement($archivecreated);
    }

    /**
     * Get archivecreated
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArchivecreated()
    {
        return $this->archivecreated;
    }

    /**
     * Add tasks
     *
     * @param \JCSGYK\AdminBundle\Entity\Task $tasks
     * @return User
     */
    public function addTask(\JCSGYK\AdminBundle\Entity\Task $tasks)
    {
        $this->tasks[] = $tasks;

        return $this;
    }

    /**
     * Remove tasks
     *
     * @param \JCSGYK\AdminBundle\Entity\Task $tasks
     */
    public function removeTask(\JCSGYK\AdminBundle\Entity\Task $tasks)
    {
        $this->tasks->removeElement($tasks);
    }

    /**
     * Get tasks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTasks()
    {
        return $this->tasks;
    }
}