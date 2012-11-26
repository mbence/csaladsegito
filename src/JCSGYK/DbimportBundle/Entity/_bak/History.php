<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * History
 *
 * @ORM\Table(name="History")
 * @ORM\Entity
 */
class History
{
    /**
     * @var integer
     *
     * @ORM\Column(name="History_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $historyId;

    /**
     * @var integer
     *
     * @ORM\Column(name="ObjectCode", type="integer", nullable=false)
     */
    private $objectcode;

    /**
     * @var integer
     *
     * @ORM\Column(name="ActionCode", type="integer", nullable=false)
     */
    private $actioncode;

    /**
     * @var integer
     *
     * @ORM\Column(name="Object_ID", type="integer", nullable=false)
     */
    private $objectId;

    /**
     * @var integer
     *
     * @ORM\Column(name="DeletedBy_ID", type="integer", nullable=false)
     */
    private $deletedbyId;

    /**
     * @var string
     *
     * @ORM\Column(name="DeletedBy_T", type="text", nullable=false)
     */
    private $deletedbyT;

    /**
     * @var string
     *
     * @ORM\Column(name="DeletedOn_T", type="text", nullable=false)
     */
    private $deletedonT;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="text", nullable=false)
     */
    private $description;



    /**
     * Get historyId
     *
     * @return integer 
     */
    public function getHistoryId()
    {
        return $this->historyId;
    }

    /**
     * Set objectcode
     *
     * @param integer $objectcode
     * @return History
     */
    public function setObjectcode($objectcode)
    {
        $this->objectcode = $objectcode;
    
        return $this;
    }

    /**
     * Get objectcode
     *
     * @return integer 
     */
    public function getObjectcode()
    {
        return $this->objectcode;
    }

    /**
     * Set actioncode
     *
     * @param integer $actioncode
     * @return History
     */
    public function setActioncode($actioncode)
    {
        $this->actioncode = $actioncode;
    
        return $this;
    }

    /**
     * Get actioncode
     *
     * @return integer 
     */
    public function getActioncode()
    {
        return $this->actioncode;
    }

    /**
     * Set objectId
     *
     * @param integer $objectId
     * @return History
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    
        return $this;
    }

    /**
     * Get objectId
     *
     * @return integer 
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set deletedbyId
     *
     * @param integer $deletedbyId
     * @return History
     */
    public function setDeletedbyId($deletedbyId)
    {
        $this->deletedbyId = $deletedbyId;
    
        return $this;
    }

    /**
     * Get deletedbyId
     *
     * @return integer 
     */
    public function getDeletedbyId()
    {
        return $this->deletedbyId;
    }

    /**
     * Set deletedbyT
     *
     * @param string $deletedbyT
     * @return History
     */
    public function setDeletedbyT($deletedbyT)
    {
        $this->deletedbyT = $deletedbyT;
    
        return $this;
    }

    /**
     * Get deletedbyT
     *
     * @return string 
     */
    public function getDeletedbyT()
    {
        return $this->deletedbyT;
    }

    /**
     * Set deletedonT
     *
     * @param string $deletedonT
     * @return History
     */
    public function setDeletedonT($deletedonT)
    {
        $this->deletedonT = $deletedonT;
    
        return $this;
    }

    /**
     * Get deletedonT
     *
     * @return string 
     */
    public function getDeletedonT()
    {
        return $this->deletedonT;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return History
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
}