<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Relation
 *
 * @ORM\Table(name="relation")
 * @ORM\Entity()
 */
class Relation
{
    /** parent types */
    const MOTHER = 1;
    const FATHER = 2;
    const GUARDIAN = 3;

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
     * @ORM\ManyToOne(targetEntity="Client", fetch="EAGER")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var integer
     *
     * @ORM\Column(name="child_id", type="integer", nullable=false)
     */
    private $childId;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
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
     * Set childId
     *
     * @param integer $childId
     * @return Relation
     */
    public function setChildId($childId)
    {
        $this->childId = $childId;

        return $this;
    }

    /**
     * Get childId
     *
     * @return integer
     */
    public function getChildId()
    {
        return $this->childId;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Relation
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

    /**
     * Set parent
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $parent
     * @return Relation
     */
    public function setParent(\JCSGYK\AdminBundle\Entity\Client $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \JCSGYK\AdminBundle\Entity\Client
     */
    public function getParent()
    {
        return $this->parent;
    }
}