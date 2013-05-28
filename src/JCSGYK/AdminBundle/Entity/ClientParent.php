<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ClientParent
 *
 * @ORM\Table(name="client_parent")
 * @ORM\Entity()
 */
class ClientParent
{
    /** parent types */
    const MOTHER = 1;
    const FATHER = 2;
    const GUARDIAN = 3;

    /**
     * @ORM\ManyToOne(targetEntity="Client", fetch="EAGER")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $parent;

    /**
     * @var integer
     *
     * @ORM\Column(name="child_id", type="integer", nullable=false)
     * @ORM\Id
     */
    private $childId;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;


    /**
     * Set childId
     *
     * @param integer $childId
     * @return ClientParent
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
     * @return ClientParent
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
     * @return ClientParent
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