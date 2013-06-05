<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\SecurityContext;

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
     * @Assert\NotBlank()
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    private $parameters;

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

    /**
     * @var integer
     *
     * @ORM\Column(name="is_deleted", type="integer", nullable=true)
     */
    private $isDeleted;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setModifiedAt(new \DateTime());
        $this->setIsDeleted(false);
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

    /**
     * Set isDeleted
     *
     * @param integer $isDeleted
     * @return Event
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return integer
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Decide if the given user can edit this record
     *
     * A user can edit a certain record if
     * - the events problem is active
     * - she has ROLE_ADMIN
     * - she is the creator of the record
     * - she is the assignee of this events problem
     *
     * @param SecurityContext $sec
     */
    public function canEdit(SecurityContext $sec)
    {
        $user_id = $sec->getToken()->getUser()->getId();

        return $this->problem->getIsActive() == 1 && (
            $sec->isGranted('ROLE_ADMIN') ||
            $this->creator->getID() == $user_id ||
            ($this->problem->getAssignee() && $this->problem->getAssignee()->getId() == $user_id)
        );
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
     * @param int $groupid optional, if not provided, the first value will get returned
     * @return mixed param value
     */
    public function getParam($groupid = null)
    {
        $plist = $this->getParams();

        if (!is_null($groupid)) {
            return isset($plist[$groupid]) ? $plist[$groupid] : null;
        }
        else {
            return reset($plist);
        }
    }

    /**
     * Checks if there are any parameters set
     * @return boolean
     */
    public function hasParams()
    {
        $has = false;
        $params = $this->getParams();
        if (!empty($params) && is_array($params)) {
            foreach ($params as $param) {
                if (!empty($param)) {
                    $has = true;
                    break;
                }
            }
        }

        return $has;
    }
}