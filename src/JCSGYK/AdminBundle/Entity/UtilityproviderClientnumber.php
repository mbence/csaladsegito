<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilityproviderClientnumber
 *
 * @ORM\Table(name="utilityprovider_clientnumber")
 * @ORM\Entity(repositoryClass="JCSGYK\AdminBundle\Entity\UtilityproviderClientnumberRepository")
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
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="utilityprovidernumbers")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var \Utilityprovider
     *
     * @ORM\ManyToOne(targetEntity="Utilityprovider")
     * @ORM\JoinColumn(name="utilityprovider_id", referencedColumnName="id")
     */
    private $utilityprovider;

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
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @return UtilityproviderId
     */
    public function setClient(\JCSGYK\AdminBundle\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \JCSGYK\AdminBundle\Entity\Client
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
     * @param \JCSGYK\AdminBundle\Entity\Utilityprovider $utilityprovider
     * @return UtilityproviderClientnumber
     */
    public function setUtilityprovider(\JCSGYK\AdminBundle\Entity\Utilityprovider $utilityprovider = null)
    {
        $this->utilityprovider = $utilityprovider;

        return $this;
    }

    /**
     * Get utilityprovider
     *
     * @return \JCSGYK\AdminBundle\Entity\Utilityprovider
     */
    public function getUtilityprovider()
    {
        return $this->utilityprovider;
    }
}