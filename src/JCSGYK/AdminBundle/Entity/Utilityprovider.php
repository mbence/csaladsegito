<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Utilityprovider
 *
 * @ORM\Table(name="utilityprovider")
 * @ORM\Entity
 */
class Utilityprovider
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
     * @ORM\Column(name="company_id", type="integer", nullable=true)
     */
    private $companyId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @ORM\OneToMany(targetEntity="UtilityproviderId", mappedBy="utilityprovider")
     */
    private $utilityproviderids;

    public function __construct()
    {
        $this->utilityproviderids = new ArrayCollection();
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
     * @return Utilityprovider
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
     * @return Utilityprovider
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return Utilityprovider
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
     * Add utilityproviderids
     *
     * @param \JCSGYK\AdminBundle\Entity\UtilityproviderId $utilityproviderids
     * @return Utilityprovider
     */
    public function addUtilityproviderid(\JCSGYK\AdminBundle\Entity\UtilityproviderId $utilityproviderids)
    {
        $this->utilityproviderids[] = $utilityproviderids;
    
        return $this;
    }

    /**
     * Remove utilityproviderids
     *
     * @param \JCSGYK\AdminBundle\Entity\UtilityproviderId $utilityproviderids
     */
    public function removeUtilityproviderid(\JCSGYK\AdminBundle\Entity\UtilityproviderId $utilityproviderids)
    {
        $this->utilityproviderids->removeElement($utilityproviderids);
    }

    /**
     * Get utilityproviderids
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUtilityproviderids()
    {
        return $this->utilityproviderids;
    }
}