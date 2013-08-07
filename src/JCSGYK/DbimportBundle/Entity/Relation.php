<?php

namespace JCSGYK\DbimportBundle\Entity;

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
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    private $parentId;

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
     * Set parent_id
     *
     * @param integer $parent_id
     * @return Relation
     */
    public function setParentId($parent_id = null)
    {
        $this->parentId = $parent_id;

        return $this;
    }

    /**
     * Get parent_id
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }
}