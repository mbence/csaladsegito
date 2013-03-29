<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Event
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
     * @var \Problem
     *
     * @ORM\ManyToOne(targetEntity="Problem", inversedBy="events", fetch="EAGER")
     * @ORM\JoinColumn(name="problem_id", referencedColumnName="id")
     */
    private $problem;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=true)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id")
     */
    private $modifier;

    /**
     * @var integer
     *
     * @ORM\Column(name="title_code", type="integer", nullable=true)
     */
    private $titleCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="forward_code", type="integer", nullable=true)
     */
    private $forwardCode;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_code", type="integer", nullable=true)
     */
    private $activityCode;

    /**
     * @var \Date
     *
     * @ORM\Column(name="event_date", type="date", nullable=true)
     */
    private $eventDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="client_visit", type="boolean", nullable=true)
     */
    private $clientVisit;

    /**
     * @var integer
     *
     * @ORM\Column(name="client_cancel", type="boolean", nullable=true)
     */
    private $clientCancel;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=255, nullable=true)
     */
    private $attachment;


    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setModifiedAt(new \DateTime());
    }

    /**
     * @ORM\PreUpdate
     */
    public function setModifiedAtValue()
    {
       $this->setModifiedAt(new \DateTime());
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
     * Set description
     *
     * @param string $description
     * @return Event
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
     * Set type
     *
     * @param integer $type
     * @return Event
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Event
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
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     * @return Event
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
     * Set titleCode
     *
     * @param integer $titleCode
     * @return Event
     */
    public function setTitleCode($titleCode)
    {
        $this->titleCode = $titleCode;

        return $this;
    }

    /**
     * Get titleCode
     *
     * @return integer
     */
    public function getTitleCode()
    {
        return $this->titleCode;
    }

    /**
     * Set forwardCode
     *
     * @param integer $forwardCode
     * @return Event
     */
    public function setForwardCode($forwardCode)
    {
        $this->forwardCode = $forwardCode;

        return $this;
    }

    /**
     * Get forwardCode
     *
     * @return integer
     */
    public function getForwardCode()
    {
        return $this->forwardCode;
    }

    /**
     * Set activityCode
     *
     * @param integer $activityCode
     * @return Event
     */
    public function setActivityCode($activityCode)
    {
        $this->activityCode = $activityCode;

        return $this;
    }

    /**
     * Get activityCode
     *
     * @return integer
     */
    public function getActivityCode()
    {
        return $this->activityCode;
    }

    /**
     * Set eventDate
     *
     * @param \Date $eventDate
     * @return Event
     */
    public function setEventDate($eventDate)
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    /**
     * Get eventDate
     *
     * @return \Date
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * Set clientVisit
     *
     * @param integer $clientVisit
     * @return Event
     */
    public function setClientVisit($clientVisit)
    {
        $this->clientVisit = $clientVisit;

        return $this;
    }

    /**
     * Get clientVisit
     *
     * @return integer
     */
    public function getClientVisit()
    {
        return $this->clientVisit;
    }

    /**
     * Set clientCancel
     *
     * @param integer $clientCancel
     * @return Event
     */
    public function setClientCancel($clientCancel)
    {
        $this->clientCancel = $clientCancel;

        return $this;
    }

    /**
     * Get clientCancel
     *
     * @return integer
     */
    public function getClientCancel()
    {
        return $this->clientCancel;
    }

    /**
     * Set attachment
     *
     * @param string $attachment
     * @return Event
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
     * Set creator
     *
     * @param \JCSGYK\AdminBundle\Entity\User $creator
     * @return Event
     */
    public function setCreator(\JCSGYK\AdminBundle\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set modifier
     *
     * @param \JCSGYK\AdminBundle\Entity\User $modifier
     * @return Event
     */
    public function setModifier(\JCSGYK\AdminBundle\Entity\User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Set problem
     *
     * @param \JCSGYK\AdminBundle\Entity\Problem $problem
     * @return Event
     */
    public function setProblem(\JCSGYK\AdminBundle\Entity\Problem $problem = null)
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * Get problem
     *
     * @return \JCSGYK\AdminBundle\Entity\Problem
     */
    public function getProblem()
    {
        return $this->problem;
    }
}