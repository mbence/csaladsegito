<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Company
 *
 * @ORM\Table(name="company")
 * @ORM\Entity
 */
class Company
{
    const CONTINUOUS = 0;
    const BY_YEAR = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="short_name", type="string", length=16, nullable=true)
     */
    private $shortName;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=255, nullable=true)
     */
    private $host;

    /**
     * @var string
     *
     * @ORM\Column(name="types", type="string", length=16, nullable=true)
     */
    private $types;

    /**
     * @var integer
     *
     * @ORM\Column(name="sequence_policy", type="integer", nullable=true)
     */
    private $sequencePolicy;

    /**
     * @var string
     *
     * @ORM\Column(name="case_number_template", type="string", length=64, nullable=true)
     */
    private $caseNumberTemplate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;


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
     * Set shortName
     *
     * @param string $shortName
     * @return Company
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;

        return $this;
    }

    /**
     * Get shortName
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Company
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
     * Set host
     *
     * @param string $host
     * @return Company
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
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
     * Set types
     *
     * @param string $types
     * @return Company
     */
    public function setTypes($types)
    {
        $this->types = $types;

        return $this;
    }

    /**
     * Get types
     *
     * @return string
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Set sequencePolicy
     *
     * @param integer $sequencePolicy
     * @return Company
     */
    public function setSequencePolicy($sequencePolicy)
    {
        $this->sequencePolicy = $sequencePolicy;

        return $this;
    }

    /**
     * Get sequencePolicy
     *
     * @return integer
     */
    public function getSequencePolicy()
    {
        return $this->sequencePolicy;
    }

    /**
     * Set caseNumberTemplate
     *
     * @param string $caseNumberTemplate
     * @return Company
     */
    public function setCaseNumberTemplate($caseNumberTemplate)
    {
        $this->caseNumberTemplate = $caseNumberTemplate;
    
        return $this;
    }

    /**
     * Get caseNumberTemplate
     *
     * @return string 
     */
    public function getCaseNumberTemplate()
    {
        return $this->caseNumberTemplate;
    }
}