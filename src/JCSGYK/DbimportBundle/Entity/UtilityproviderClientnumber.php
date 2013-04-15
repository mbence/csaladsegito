<?php

namespace JCSGYK\DbimportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilityproviderClientnumber
 *
 * @ORM\Table(name="utilityprovider_clientnumber")
 * @ORM\Entity(")
 */
class UtilityproviderClientnumber
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
     * @var \Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="utilityproviders")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var \UtilityproviderId
     *
     * @ORM\Column(name="utilityprovider_id", type="integer", nullable=true)
     */
    private $utilityproviderId;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=60, nullable=true)
     */
    private $value;



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
     * Set client
     *
     * @param \JCSGYK\DbimportBundle\Entity\Client $client
     * @return UtilityproviderId
     */
    public function setClient(\JCSGYK\DbimportBundle\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \JCSGYK\DbimportBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return UtilityproviderClientnumber
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set utilityprovider
     *
     * @param integer $utilityprovider
     * @return UtilityproviderClientnumber
     */
    public function setUtilityproviderId($utilityprovider = null)
    {
        $this->utilityproviderId = $utilityprovider;

        return $this;
    }

    /**
     * Get utilityprovider
     *
     * @return integer
     */
    public function getUtilityproviderId()
    {
        return $this->utilityproviderId;
    }
}