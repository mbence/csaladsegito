<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Events
 *
 * @ORM\Table(name="Events")
 * @ORM\Entity
 */
class Events
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Event_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $eventId;

    /**
     * @var string
     *
     * @ORM\Column(name="Title", type="text", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="Desciption", type="text", nullable=true)
     */
    private $desciption;

    /**
     * @var integer
     *
     * @ORM\Column(name="Type", type="integer", nullable=true)
     */
    private $type;

    /**
     * @var float
     *
     * @ORM\Column(name="CreatedOn", type="float", nullable=true)
     */
    private $createdon;

    /**
     * @var integer
     *
     * @ORM\Column(name="CreatedBy_ID", type="integer", nullable=false)
     */
    private $createdbyId;

    /**
     * @var float
     *
     * @ORM\Column(name="ModifiedOn", type="float", nullable=true)
     */
    private $modifiedon;

    /**
     * @var integer
     *
     * @ORM\Column(name="ModifiedBy_ID", type="integer", nullable=false)
     */
    private $modifiedbyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="TitleCode", type="integer", nullable=true)
     */
    private $titlecode;

    /**
     * @var integer
     *
     * @ORM\Column(name="ForwardCode", type="integer", nullable=true)
     */
    private $forwardcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="GroupCode", type="integer", nullable=true)
     */
    private $groupcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="ActivityCode", type="integer", nullable=true)
     */
    private $activitycode;

    /**
     * @var float
     *
     * @ORM\Column(name="EventDate", type="float", nullable=false)
     */
    private $eventdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="ClientVisit", type="integer", nullable=true)
     */
    private $clientvisit;

    /**
     * @var integer
     *
     * @ORM\Column(name="ClientCancel", type="integer", nullable=true)
     */
    private $clientcancel;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpenedBy_ID", type="integer", nullable=true)
     */
    private $openedbyId;

    /**
     * @var string
     *
     * @ORM\Column(name="DocFile", type="text", nullable=true)
     */
    private $docfile;

    /**
     * @var string
     *
     * @ORM\Column(name="FieldText", type="text", nullable=true)
     */
    private $fieldtext;

    /**
     * @var integer
     *
     * @ORM\Column(name="FieldNum", type="integer", nullable=true)
     */
    private $fieldnum;



    /**
     * Get eventId
     *
     * @return integer 
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Events
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
     * Set desciption
     *
     * @param string $desciption
     * @return Events
     */
    public function setDesciption($desciption)
    {
        $this->desciption = $desciption;
    
        return $this;
    }

    /**
     * Get desciption
     *
     * @return string 
     */
    public function getDesciption()
    {
        return $this->desciption;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Events
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
     * Set createdon
     *
     * @param float $createdon
     * @return Events
     */
    public function setCreatedon($createdon)
    {
        $this->createdon = $createdon;
    
        return $this;
    }

    /**
     * Get createdon
     *
     * @return float 
     */
    public function getCreatedon()
    {
        return $this->createdon;
    }

    /**
     * Set createdbyId
     *
     * @param integer $createdbyId
     * @return Events
     */
    public function setCreatedbyId($createdbyId)
    {
        $this->createdbyId = $createdbyId;
    
        return $this;
    }

    /**
     * Get createdbyId
     *
     * @return integer 
     */
    public function getCreatedbyId()
    {
        return $this->createdbyId;
    }

    /**
     * Set modifiedon
     *
     * @param float $modifiedon
     * @return Events
     */
    public function setModifiedon($modifiedon)
    {
        $this->modifiedon = $modifiedon;
    
        return $this;
    }

    /**
     * Get modifiedon
     *
     * @return float 
     */
    public function getModifiedon()
    {
        return $this->modifiedon;
    }

    /**
     * Set modifiedbyId
     *
     * @param integer $modifiedbyId
     * @return Events
     */
    public function setModifiedbyId($modifiedbyId)
    {
        $this->modifiedbyId = $modifiedbyId;
    
        return $this;
    }

    /**
     * Get modifiedbyId
     *
     * @return integer 
     */
    public function getModifiedbyId()
    {
        return $this->modifiedbyId;
    }

    /**
     * Set titlecode
     *
     * @param integer $titlecode
     * @return Events
     */
    public function setTitlecode($titlecode)
    {
        $this->titlecode = $titlecode;
    
        return $this;
    }

    /**
     * Get titlecode
     *
     * @return integer 
     */
    public function getTitlecode()
    {
        return $this->titlecode;
    }

    /**
     * Set forwardcode
     *
     * @param integer $forwardcode
     * @return Events
     */
    public function setForwardcode($forwardcode)
    {
        $this->forwardcode = $forwardcode;
    
        return $this;
    }

    /**
     * Get forwardcode
     *
     * @return integer 
     */
    public function getForwardcode()
    {
        return $this->forwardcode;
    }

    /**
     * Set groupcode
     *
     * @param integer $groupcode
     * @return Events
     */
    public function setGroupcode($groupcode)
    {
        $this->groupcode = $groupcode;
    
        return $this;
    }

    /**
     * Get groupcode
     *
     * @return integer 
     */
    public function getGroupcode()
    {
        return $this->groupcode;
    }

    /**
     * Set activitycode
     *
     * @param integer $activitycode
     * @return Events
     */
    public function setActivitycode($activitycode)
    {
        $this->activitycode = $activitycode;
    
        return $this;
    }

    /**
     * Get activitycode
     *
     * @return integer 
     */
    public function getActivitycode()
    {
        return $this->activitycode;
    }

    /**
     * Set eventdate
     *
     * @param float $eventdate
     * @return Events
     */
    public function setEventdate($eventdate)
    {
        $this->eventdate = $eventdate;
    
        return $this;
    }

    /**
     * Get eventdate
     *
     * @return float 
     */
    public function getEventdate()
    {
        return $this->eventdate;
    }

    /**
     * Set clientvisit
     *
     * @param integer $clientvisit
     * @return Events
     */
    public function setClientvisit($clientvisit)
    {
        $this->clientvisit = $clientvisit;
    
        return $this;
    }

    /**
     * Get clientvisit
     *
     * @return integer 
     */
    public function getClientvisit()
    {
        return $this->clientvisit;
    }

    /**
     * Set clientcancel
     *
     * @param integer $clientcancel
     * @return Events
     */
    public function setClientcancel($clientcancel)
    {
        $this->clientcancel = $clientcancel;
    
        return $this;
    }

    /**
     * Get clientcancel
     *
     * @return integer 
     */
    public function getClientcancel()
    {
        return $this->clientcancel;
    }

    /**
     * Set openedbyId
     *
     * @param integer $openedbyId
     * @return Events
     */
    public function setOpenedbyId($openedbyId)
    {
        $this->openedbyId = $openedbyId;
    
        return $this;
    }

    /**
     * Get openedbyId
     *
     * @return integer 
     */
    public function getOpenedbyId()
    {
        return $this->openedbyId;
    }

    /**
     * Set docfile
     *
     * @param string $docfile
     * @return Events
     */
    public function setDocfile($docfile)
    {
        $this->docfile = $docfile;
    
        return $this;
    }

    /**
     * Get docfile
     *
     * @return string 
     */
    public function getDocfile()
    {
        return $this->docfile;
    }

    /**
     * Set fieldtext
     *
     * @param string $fieldtext
     * @return Events
     */
    public function setFieldtext($fieldtext)
    {
        $this->fieldtext = $fieldtext;
    
        return $this;
    }

    /**
     * Get fieldtext
     *
     * @return string 
     */
    public function getFieldtext()
    {
        return $this->fieldtext;
    }

    /**
     * Set fieldnum
     *
     * @param integer $fieldnum
     * @return Events
     */
    public function setFieldnum($fieldnum)
    {
        $this->fieldnum = $fieldnum;
    
        return $this;
    }

    /**
     * Get fieldnum
     *
     * @return integer 
     */
    public function getFieldnum()
    {
        return $this->fieldnum;
    }
}