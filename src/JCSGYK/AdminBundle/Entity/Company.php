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
     * @ORM\Column(name="types", type="string", length=64, nullable=true)
     */
    private $types;

    /**
     * @var string
     *
     * @ORM\Column(name="sequence_policy", type="string", length=64, nullable=true)
     */
    private $sequencePolicy;

    /**
     * @var string
     *
     * @ORM\Column(name="case_number_template", type="text", nullable=true)
     */
    private $caseNumberTemplate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     */
    private $logo;

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
     * @param array $types
     * @return Company
     */
    public function setTypes(array $types)
    {
        $this->types = json_encode($types);

        return $this;
    }

    /**
     * Get types
     *
     * @return array
     */
    public function getTypes()
    {
        return json_decode($this->types, true);
    }

    /**
     * Set sequencePolicy
     *
     * @param array $sequencePolicy
     * @return Company
     */
    public function setSequencePolicy(array $sequencePolicy)
    {
        $this->sequencePolicy = json_encode($sequencePolicy);

        return $this;
    }

    /**
     * Get sequencePolicy
     *
     * @return array
     */
    public function getSequencePolicy()
    {
        return json_decode($this->sequencePolicy, true);
    }

    /**
     * Set caseNumberTemplate
     *
     * @param array $caseNumberTemplate
     * @return Company
     */
    public function setCaseNumberTemplate(array $caseNumberTemplate)
    {
        $this->caseNumberTemplate = json_encode($caseNumberTemplate);

        return $this;
    }

    /**
     * Get caseNumberTemplate
     *
     * @return array
     */
    public function getCaseNumberTemplate()
    {
        return json_decode($this->caseNumberTemplate, true);
    }

    /**
     * Set logo
     *
     * @param string $logo
     * @return Company
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }
}
