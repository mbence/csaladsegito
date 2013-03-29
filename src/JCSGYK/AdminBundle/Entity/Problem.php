<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Problem
 *
 * @ORM\Table(name="problem")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Problem
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
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="problems")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $title;

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
     * @var integer
     *
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    private $level;

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
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     * @ORM\JoinColumn(name="assigned_to", referencedColumnName="id")
     */
    private $assignee;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="closed_by", referencedColumnName="id")
     */
    private $closer;

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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="opened_by", referencedColumnName="id")
     */
    private $opener;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=255, nullable=true)
     */
    private $attachment;

    /**
     * @ORM\OneToMany(targetEntity="Debt", mappedBy="problem")
     */
    private $debts;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="problem")
     * @ORM\OrderBy({"eventDate" = "DESC", "createdAt" = "DESC"})
     */
    private $events;


    public function __construct()
    {
        $this->debts = new ArrayCollection();
        $this->events = new ArrayCollection();
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
     * Set type
     *
     * @param integer $type
     * @return Problem
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
     * Set level
     *
     * @param integer $level
     * @return Problem
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
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
     * Set client
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @return Problem
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

    /**
     * Set creator
     *
     * @param \JCSGYK\AdminBundle\Entity\User $creator
     * @return Problem
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
     * @return Problem
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
     * Set assignee
     *
     * @param \JCSGYK\AdminBundle\Entity\User $assignee
     * @return Problem
     */
    public function setAssignee(\JCSGYK\AdminBundle\Entity\User $assignee = null)
    {
        $this->assignee = $assignee;

        return $this;
    }

    /**
     * Get assignee
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * Set closer
     *
     * @param \JCSGYK\AdminBundle\Entity\User $closer
     * @return Problem
     */
    public function setCloser(\JCSGYK\AdminBundle\Entity\User $closer = null)
    {
        $this->closer = $closer;

        return $this;
    }

    /**
     * Get closer
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getCloser()
    {
        return $this->closer;
    }

    /**
     * Set opener
     *
     * @param \JCSGYK\AdminBundle\Entity\User $opener
     * @return Problem
     */
    public function setOpener(\JCSGYK\AdminBundle\Entity\User $opener = null)
    {
        $this->opener = $opener;

        return $this;
    }

    /**
     * Get opener
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getOpener()
    {
        return $this->opener;
    }

    /**
     * Add debts
     *
     * @param \JCSGYK\AdminBundle\Entity\Debt $debts
     * @return Problem
     */
    public function addDebt(\JCSGYK\AdminBundle\Entity\Debt $debts)
    {
        $this->debts[] = $debts;

        return $this;
    }

    /**
     * Remove debts
     *
     * @param \JCSGYK\AdminBundle\Entity\Debt $debts
     */
    public function removeDebt(\JCSGYK\AdminBundle\Entity\Debt $debts)
    {
        $this->debts->removeElement($debts);
    }

    /**
     * Get debts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDebts()
    {
        return $this->debts;
    }

    /**
     * Add event
     *
     * @param \JCSGYK\AdminBundle\Entity\Event $event
     * @return Problem
     */
    public function addEvent(\JCSGYK\AdminBundle\Entity\Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event
     *
     * @param \JCSGYK\AdminBundle\Entity\Event $event
     */
    public function removeEvent(\JCSGYK\AdminBundle\Entity\Event $event)
    {
        $this->events->removeElement($event);
    }

    /**
     * Get events
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }
}