<?php

namespace JCSGYK\AdminBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="admin_user")
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

    public function __construct($salt = null)
    {
        parent::__construct();
        // your own logic
        if (!is_null($salt)) {
            $this->salt = $salt;
        }
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
}