<?php

namespace JCSGYK\DbimportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TmpIdmap
 *
 * @ORM\Table(name="_tmp_idmap")
 * @ORM\Entity
 */
class TmpIdmap
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     * @ORM\Id
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="old_id", type="integer", nullable=true)
     */
    private $oldId;



    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set oldId
     *
     * @param integer $oldId
     * @return TmpIdmap
     */
    public function setOldId($oldId)
    {
        $this->oldId = $oldId;

        return $this;
    }

    /**
     * Get oldId
     *
     * @return integer
     */
    public function getOldId()
    {
        return $this->oldId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return TmpIdmap
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }
}