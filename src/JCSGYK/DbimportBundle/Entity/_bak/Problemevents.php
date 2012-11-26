<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Problemevents
 *
 * @ORM\Table(name="ProblemEvents")
 * @ORM\Entity
 */
class Problemevents
{
    /**
     * @var integer
     *
     * @ORM\Column(name="PE_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $peId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Problem_ID", type="integer", nullable=false)
     */
    private $problemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Event_ID", type="integer", nullable=false)
     */
    private $eventId;



    /**
     * Get peId
     *
     * @return integer 
     */
    public function getPeId()
    {
        return $this->peId;
    }

    /**
     * Set problemId
     *
     * @param integer $problemId
     * @return Problemevents
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
     * Set eventId
     *
     * @param integer $eventId
     * @return Problemevents
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    
        return $this;
    }

    /**
     * Get eventId
     *
     * @return integer 
     */
    public function getEventId()
    {
        return $this->eventId;
    }
}