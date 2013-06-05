<?php

namespace JCSGYK\DbimportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity
 */
class Event
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
     * @ORM\Column(name="problem_id", type="integer", nullable=false)
     */
    private $problemId;

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
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", nullable=true)
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
     * @ORM\Column(name="modified_by", type="integer", nullable=true)
     */
    private $modifiedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="event_date", type="datetime", nullable=true)
     */
    private $eventDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="client_visit", type="boolean", nullable=true)
     */
    private $clientVisit;

    /**
     * @var boolean
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set problemId
     *
     * @param integer $problemId
     * @return Event
     */
    public function setProblemId($problemId)
    {
        $this->problemId = $problemId;

        return $this;
    }

    /**
     * Get problemId
     *
     * @return integer
     */
    public function getProblemId()
    {
        return $this->problemId;
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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return Event
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
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     * @return Event
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
     * @param \DateTime $eventDate
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
     * @return \DateTime
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * Set clientVisit
     *
     * @param boolean $clientVisit
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
     * @return boolean
     */
    public function getClientVisit()
    {
        return $this->clientVisit;
    }

    /**
     * Set clientCancel
     *
     * @param boolean $clientCancel
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
     * @return boolean
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
     * Set id
     *
     * @param integer $id
     * @return Event
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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