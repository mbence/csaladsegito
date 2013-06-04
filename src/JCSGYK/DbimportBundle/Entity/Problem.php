<?php

namespace JCSGYK\DbimportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Problem
 *
 * @ORM\Table(name="problem")
 * @ORM\Entity
 */
class Problem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="client_id", type="integer", nullable=false)
     */
    private $clientId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    private $parameters;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_active", type="integer", nullable=true)
     */
    private $isActive;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="modified_by", type="integer", nullable=false)
     */
    private $modifiedBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="assigned_to", type="integer", nullable=false)
     */
    private $assignedTo;

    /**
     * @var integer
     *
     * @ORM\Column(name="closed_by", type="integer", nullable=false)
     */
    private $closedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="closed_at", type="datetime", nullable=true)
     */
    private $closedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="close_code", type="integer", nullable=true)
     */
    private $closeCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="confirmed_by", type="integer", nullable=false)
     */
    private $confirmedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="confirmed_at", type="datetime", nullable=true)
     */
    private $confirmedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="opened_by", type="integer", nullable=false)
     */
    private $openedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=255, nullable=true)
     */
    private $attachment;

    /**
     * Set id
     *
     * @param integer $id
     * @return Problem
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set clientId
     *
     * @param integer $clientId
     * @return Problem
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get clientId
     *
     * @return integer
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Problem
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Problem
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set parameters
     *
     * @param integer $parameters
     * @return Problem
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return integer
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Problem
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Problem
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     * @return Problem
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     * @return Problem
     */
    public function setModifiedBy($modifiedBy)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return integer
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set assignedTo
     *
     * @param integer $assignedTo
     * @return Problem
     */
    public function setAssignedTo($assignedTo)
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    /**
     * Get assignedTo
     *
     * @return integer
     */
    public function getAssignedTo()
    {
        return $this->assignedTo;
    }

    /**
     * Set closedBy
     *
     * @param integer $closedBy
     * @return Problem
     */
    public function setClosedBy($closedBy)
    {
        $this->closedBy = $closedBy;

        return $this;
    }

    /**
     * Get closedBy
     *
     * @return integer
     */
    public function getClosedBy()
    {
        return $this->closedBy;
    }

    /**
     * Set closedAt
     *
     * @param \DateTime $closedAt
     * @return Problem
     */
    public function setClosedAt($closedAt)
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * Get closedAt
     *
     * @return \DateTime
     */
    public function getClosedAt()
    {
        return $this->closedAt;
    }

    /**
     * Set closeCode
     *
     * @param integer $closeCode
     * @return Problem
     */
    public function setCloseCode($closeCode)
    {
        $this->closeCode = $closeCode;

        return $this;
    }

    /**
     * Get closeCode
     *
     * @return integer
     */
    public function getCloseCode()
    {
        return $this->closeCode;
    }

    /**
     * Set confirmedBy
     *
     * @param integer $confirmedBy
     * @return Problem
     */
    public function setConfirmedBy($confirmedBy)
    {
        $this->confirmedBy = $confirmedBy;

        return $this;
    }

    /**
     * Get confirmedBy
     *
     * @return integer
     */
    public function getConfirmedBy()
    {
        return $this->confirmedBy;
    }

    /**
     * Set confirmedAt
     *
     * @param \DateTime $confirmedAt
     * @return Problem
     */
    public function setConfirmedAt($confirmedAt)
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    /**
     * Get confirmedAt
     *
     * @return \DateTime
     */
    public function getConfirmedAt()
    {
        return $this->confirmedAt;
    }

    /**
     * Set openedBy
     *
     * @param integer $openedBy
     * @return Problem
     */
    public function setOpenedBy($openedBy)
    {
        $this->openedBy = $openedBy;

        return $this;
    }

    /**
     * Get openedBy
     *
     * @return integer
     */
    public function getOpenedBy()
    {
        return $this->openedBy;
    }

    /**
     * Set attachment
     *
     * @param string $attachment
     * @return Problem
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * Get attachment
     *
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Set isActive
     *
     * @param integer $isActive
     * @return Problem
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return integer
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Json encode and set parameters
     *
     * @param array $parameters
     * @return Client
     */
    public function setParams($parameters)
    {
        $this->parameters = json_encode($parameters);

        return $this;
    }

    /**
     * Json decode and get parameters
     *
     * @return array
     */
    public function getParams()
    {
        return json_decode($this->parameters, true);
    }

    /**
     * Return a value of the parameters array
     * @param int $groupid
     * @return mixed param value
     */
    public function getParam($groupid)
    {
        $plist = $this->getParams();

        return isset($plist[$groupid]) ? $plist[$groupid] : null;
    }
}