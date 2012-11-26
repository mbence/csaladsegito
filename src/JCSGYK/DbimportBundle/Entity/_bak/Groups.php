<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Groups
 *
 * @ORM\Table(name="Groups")
 * @ORM\Entity
 */
class Groups
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Group_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $groupId;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="text", nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="Topic", type="integer", nullable=true)
     */
    private $topic;

    /**
     * @var string
     *
     * @ORM\Column(name="Desc", type="text", nullable=true)
     */
    private $desc;

    /**
     * @var string
     *
     * @ORM\Column(name="Note", type="text", nullable=true)
     */
    private $note;

    /**
     * @var integer
     *
     * @ORM\Column(name="Leader1_ID", type="integer", nullable=false)
     */
    private $leader1Id;

    /**
     * @var integer
     *
     * @ORM\Column(name="Leader2_ID", type="integer", nullable=false)
     */
    private $leader2Id;

    /**
     * @var string
     *
     * @ORM\Column(name="TimeTable", type="text", nullable=true)
     */
    private $timetable;

    /**
     * @var integer
     *
     * @ORM\Column(name="MaxSize", type="integer", nullable=true)
     */
    private $maxsize;

    /**
     * @var integer
     *
     * @ORM\Column(name="Status", type="integer", nullable=false)
     */
    private $status;

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
     * @var float
     *
     * @ORM\Column(name="StartedOn", type="float", nullable=true)
     */
    private $startedon;

    /**
     * @var integer
     *
     * @ORM\Column(name="StartedBy_ID", type="integer", nullable=false)
     */
    private $startedbyId;

    /**
     * @var float
     *
     * @ORM\Column(name="FinishedOn", type="float", nullable=true)
     */
    private $finishedon;

    /**
     * @var integer
     *
     * @ORM\Column(name="FinishedBy_ID", type="integer", nullable=false)
     */
    private $finishedbyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpenedBy_ID", type="integer", nullable=false)
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
     * Get groupId
     *
     * @return integer 
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Groups
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set topic
     *
     * @param integer $topic
     * @return Groups
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
    
        return $this;
    }

    /**
     * Get topic
     *
     * @return integer 
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Set desc
     *
     * @param string $desc
     * @return Groups
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    
        return $this;
    }

    /**
     * Get desc
     *
     * @return string 
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Groups
     */
    public function setNote($note)
    {
        $this->note = $note;
    
        return $this;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set leader1Id
     *
     * @param integer $leader1Id
     * @return Groups
     */
    public function setLeader1Id($leader1Id)
    {
        $this->leader1Id = $leader1Id;
    
        return $this;
    }

    /**
     * Get leader1Id
     *
     * @return integer 
     */
    public function getLeader1Id()
    {
        return $this->leader1Id;
    }

    /**
     * Set leader2Id
     *
     * @param integer $leader2Id
     * @return Groups
     */
    public function setLeader2Id($leader2Id)
    {
        $this->leader2Id = $leader2Id;
    
        return $this;
    }

    /**
     * Get leader2Id
     *
     * @return integer 
     */
    public function getLeader2Id()
    {
        return $this->leader2Id;
    }

    /**
     * Set timetable
     *
     * @param string $timetable
     * @return Groups
     */
    public function setTimetable($timetable)
    {
        $this->timetable = $timetable;
    
        return $this;
    }

    /**
     * Get timetable
     *
     * @return string 
     */
    public function getTimetable()
    {
        return $this->timetable;
    }

    /**
     * Set maxsize
     *
     * @param integer $maxsize
     * @return Groups
     */
    public function setMaxsize($maxsize)
    {
        $this->maxsize = $maxsize;
    
        return $this;
    }

    /**
     * Get maxsize
     *
     * @return integer 
     */
    public function getMaxsize()
    {
        return $this->maxsize;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Groups
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdon
     *
     * @param float $createdon
     * @return Groups
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
     * @return Groups
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
     * @return Groups
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
     * @return Groups
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
     * Set startedon
     *
     * @param float $startedon
     * @return Groups
     */
    public function setStartedon($startedon)
    {
        $this->startedon = $startedon;
    
        return $this;
    }

    /**
     * Get startedon
     *
     * @return float 
     */
    public function getStartedon()
    {
        return $this->startedon;
    }

    /**
     * Set startedbyId
     *
     * @param integer $startedbyId
     * @return Groups
     */
    public function setStartedbyId($startedbyId)
    {
        $this->startedbyId = $startedbyId;
    
        return $this;
    }

    /**
     * Get startedbyId
     *
     * @return integer 
     */
    public function getStartedbyId()
    {
        return $this->startedbyId;
    }

    /**
     * Set finishedon
     *
     * @param float $finishedon
     * @return Groups
     */
    public function setFinishedon($finishedon)
    {
        $this->finishedon = $finishedon;
    
        return $this;
    }

    /**
     * Get finishedon
     *
     * @return float 
     */
    public function getFinishedon()
    {
        return $this->finishedon;
    }

    /**
     * Set finishedbyId
     *
     * @param integer $finishedbyId
     * @return Groups
     */
    public function setFinishedbyId($finishedbyId)
    {
        $this->finishedbyId = $finishedbyId;
    
        return $this;
    }

    /**
     * Get finishedbyId
     *
     * @return integer 
     */
    public function getFinishedbyId()
    {
        return $this->finishedbyId;
    }

    /**
     * Set openedbyId
     *
     * @param integer $openedbyId
     * @return Groups
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
     * @return Groups
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
     * @return Groups
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
     * @return Groups
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