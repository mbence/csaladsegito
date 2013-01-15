<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilityproviderId
 *
 * @ORM\Table(name="utilityprovider")
 * @ORM\Entity(repositoryClass="JCSGYK\AdminBundle\Entity\UtilityproviderRepository")
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
     * @var \Person
     *
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="utilityproviders")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    private $person;

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
     * Set person
     *
     * @param \JCSGYK\AdminBundle\Entity\Person $person
     * @return UtilityproviderId
     */
    public function setPerson(\JCSGYK\AdminBundle\Entity\Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return \JCSGYK\AdminBundle\Entity\Person
     */
    public function getPerson()
    {
        return $this->person;
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