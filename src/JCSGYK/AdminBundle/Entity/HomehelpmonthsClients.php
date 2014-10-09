<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HomehelpmonthsClients
 *
 * @ORM\Table(name="homehelpmonths_clients", uniqueConstraints={@ORM\UniqueConstraint(name="homehelpmonth_id", columns={"homehelpmonth_id", "client_id"})})
 * @ORM\Entity
 */
class HomehelpmonthsClients
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
     * @ORM\ManyToOne(targetEntity="HomehelpMonth", inversedBy="hmClients")
     * @ORM\JoinColumn(name="homehelpmonth_id", referencedColumnName="id")
     */
    private $homehelpmonth;

    /**
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_closed", type="boolean", nullable=true)
     */
    private $isClosed;



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
     * Set isClosed
     *
     * @param boolean $isClosed
     *
     * @return HomehelpmonthsClients
     */
    public function setIsClosed($isClosed)
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    /**
     * Get isClosed
     *
     * @return boolean
     */
    public function getIsClosed()
    {
        return $this->isClosed;
    }

    /**
     * Set homehelpmonth
     *
     * @param \JCSGYK\AdminBundle\Entity\HomehelpMonth $homehelpmonth
     *
     * @return HomehelpmonthsClients
     */
    public function setHomehelpmonth(\JCSGYK\AdminBundle\Entity\HomehelpMonth $homehelpmonth = null)
    {
        $this->homehelpmonth = $homehelpmonth;

        return $this;
    }

    /**
     * Get homehelpmonth
     *
     * @return \JCSGYK\AdminBundle\Entity\HomehelpMonth
     */
    public function getHomehelpmonth()
    {
        return $this->homehelpmonth;
    }

    /**
     * Set client
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     *
     * @return HomehelpmonthsClients
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
