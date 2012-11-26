<?php
namespace JCSGYK\AdminBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="inquiry")
 */

class Inquiry
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $inquiry_type_id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $user_id;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created_at;
   
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
     * Set inquiry_type_id
     *
     * @param integer $inquiryTypeId
     * @return Inquiry
     */
    public function setInquiryTypeId($inquiryTypeId)
    {
        $this->inquiry_type_id = $inquiryTypeId;
    
        return $this;
    }

    /**
     * Get inquiry_type_id
     *
     * @return integer 
     */
    public function getInquiryTypeId()
    {
        return $this->inquiry_type_id;
    }

    /**
     * Set user_id
     *
     * @param integer $userId
     * @return Inquiry
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
    
        return $this;
    }

    /**
     * Get user_id
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Inquiry
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    
        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
}