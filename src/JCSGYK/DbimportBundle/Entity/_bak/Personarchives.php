<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Personarchives
 *
 * @ORM\Table(name="PersonArchives")
 * @ORM\Entity
 */
class Personarchives
{
    /**
     * @var integer
     *
     * @ORM\Column(name="PA_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $paId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Person_ID", type="integer", nullable=false)
     */
    private $personId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Archive_ID", type="integer", nullable=false)
     */
    private $archiveId;



    /**
     * Get paId
     *
     * @return integer 
     */
    public function getPaId()
    {
        return $this->paId;
    }

    /**
     * Set personId
     *
     * @param integer $personId
     * @return Personarchives
     */
    public function setPersonId($personId)
    {
        $this->personId = $personId;
    
        return $this;
    }

    /**
     * Get personId
     *
     * @return integer 
     */
    public function getPersonId()
    {
        return $this->personId;
    }

    /**
     * Set archiveId
     *
     * @param integer $archiveId
     * @return Personarchives
     */
    public function setArchiveId($archiveId)
    {
        $this->archiveId = $archiveId;
    
        return $this;
    }

    /**
     * Get archiveId
     *
     * @return integer 
     */
    public function getArchiveId()
    {
        return $this->archiveId;
    }
}