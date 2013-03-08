<?php

namespace JCSGYK\DbimportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilityproviderId
 *
 * @ORM\Table(name="utilityprovider")
 * @ORM\Entity()
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
     * @var \Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="utilityproviders")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=60, nullable=true)
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     */
    private $type;

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
     * Set value
     *
     * @param string $value
     * @return UtilityproviderId
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
     * Set type
     *
     * @param integer $type
     * @return Utilityprovider
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
}