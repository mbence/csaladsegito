<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilityproviderId
 *
 * @ORM\Table(name="utilityprovider_id")
 * @ORM\Entity(repositoryClass="JCSGYK\AdminBundle\Entity\UtilityproviderIdRepository")
 */
class UtilityproviderId
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
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="utilityproviderids")
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
     * @var \Utilityprovider
     *
     * @ORM\ManyToOne(targetEntity="Utilityprovider", inversedBy="utilityproviderids")
     * @ORM\JoinColumn(name="utilityprovider_id", referencedColumnName="id")
     */
    private $utilityprovider;

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
     * Set utilityprovider
     *
     * @param \JCSGYK\AdminBundle\Entity\Utilityprovider $utilityprovider
     * @return UtilityproviderId
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