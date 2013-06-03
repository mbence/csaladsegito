<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientParam
 *
 * @ORM\Table(name="client_param")
 * @ORM\Entity
 */
class ClientParam
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
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="params")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var integer
     *
     * @ORM\Column(name="paramgroup_id", type="smallint", nullable=false)
     */
    private $paramgroupId;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
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
     * Set paramgroupId
     *
     * @param integer $paramgroupId
     * @return ClientParam
     */
    public function setParamgroupId($paramgroupId)
    {
        $this->paramgroupId = $paramgroupId;
    
        return $this;
    }

    /**
     * Get paramgroupId
     *
     * @return integer 
     */
    public function getParamgroupId()
    {
        return $this->paramgroupId;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return ClientParam
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
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @return ClientParam
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
}