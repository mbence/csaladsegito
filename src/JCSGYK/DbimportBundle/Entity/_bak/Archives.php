<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Archives
 *
 * @ORM\Table(name="Archives")
 * @ORM\Entity
 */
class Archives
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Archive_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $archiveId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="Reason", type="text", nullable=true)
     */
    private $reason;

    /**
     * @var integer
     *
     * @ORM\Column(name="ArchivedBy_ID", type="integer", nullable=false)
     */
    private $archivedbyId;

    /**
     * @var float
     *
     * @ORM\Column(name="ArchivedOn", type="float", nullable=false)
     */
    private $archivedon;

    /**
     * @var string
     *
     * @ORM\Column(name="ArchivedOn_T", type="text", nullable=false)
     */
    private $archivedonT;



    /**
     * Get archiveId
     *
     * @return integer 
     */
    public function getArchiveId()
    {
        return $this->archiveId;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Archives
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
     * Set reason
     *
     * @param string $reason
     * @return Archives
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    
        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set archivedbyId
     *
     * @param integer $archivedbyId
     * @return Archives
     */
    public function setArchivedbyId($archivedbyId)
    {
        $this->archivedbyId = $archivedbyId;
    
        return $this;
    }

    /**
     * Get archivedbyId
     *
     * @return integer 
     */
    public function getArchivedbyId()
    {
        return $this->archivedbyId;
    }

    /**
     * Set archivedon
     *
     * @param float $archivedon
     * @return Archives
     */
    public function setArchivedon($archivedon)
    {
        $this->archivedon = $archivedon;
    
        return $this;
    }

    /**
     * Get archivedon
     *
     * @return float 
     */
    public function getArchivedon()
    {
        return $this->archivedon;
    }

    /**
     * Set archivedonT
     *
     * @param string $archivedonT
     * @return Archives
     */
    public function setArchivedonT($archivedonT)
    {
        $this->archivedonT = $archivedonT;
    
        return $this;
    }

    /**
     * Get archivedonT
     *
     * @return string 
     */
    public function getArchivedonT()
    {
        return $this->archivedonT;
    }
}