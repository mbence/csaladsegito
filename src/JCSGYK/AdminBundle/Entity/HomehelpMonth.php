<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * HomehelpMonth
 *
 * @ORM\Table(name="homehelp_month")
 * @ORM\Entity
 */
class HomehelpMonth
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", nullable=false)
     */
    private $companyId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="social_worker", type="integer", nullable=true)
     */
    private $socialWorker;

    /**
     * @var string
     *
     * @ORM\Column(name="rowheaders", type="json_array", length=65535, nullable=true)
     */
    private $rowheaders;
    /**
     * @var string
     *
     * @ORM\Column(name="data", type="json_array", length=65535, nullable=true)
     */
    private $data;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", nullable=true)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="modified_by", type="integer", nullable=true)
     */
    private $modifiedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @ORM\OneToMany(targetEntity="HomehelpmonthsClients", mappedBy="homehelpmonth")
     **/
    private $hmClients;

    public function __construct() {
        $this->hmClients = new ArrayCollection();
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return HomehelpMonth
     */
    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return HomehelpMonth
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set socialWorker
     *
     * @param integer $socialWorker
     *
     * @return HomehelpMonth
     */
    public function setSocialWorker($socialWorker)
    {
        $this->socialWorker = $socialWorker;

        return $this;
    }

    /**
     * Get socialWorker
     *
     * @return integer
     */
    public function getSocialWorker()
    {
        return $this->socialWorker;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     *
     * @return HomehelpMonth
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return HomehelpMonth
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
     * Set modifiedBy
     *
     * @param integer $modifiedBy
     *
     * @return HomehelpMonth
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
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return HomehelpMonth
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
     * Set rowheaders
     *
     * @param array $rowheaders
     *
     * @return HomehelpMonth
     */
    public function setRowheaders($rowheaders)
    {
        $this->rowheaders = $rowheaders;

        return $this;
    }

    /**
     * Get rowheaders
     *
     * @return array
     */
    public function getRowheaders()
    {
        return $this->rowheaders;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return HomehelpMonth
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * Add hmClient
     *
     * @param \JCSGYK\AdminBundle\Entity\HomehelpmonthsClients $hmClient
     *
     * @return Homehelpmonth
     */
    public function addHmClient(\JCSGYK\AdminBundle\Entity\HomehelpmonthsClients $hmClient)
    {
        $this->hmClients[] = $hmClient;

        return $this;
    }

    /**
     * Remove hmClient
     *
     * @param \JCSGYK\AdminBundle\Entity\HomehelpmonthsClients $hmClient
     */
    public function removeHmClient(\JCSGYK\AdminBundle\Entity\HomehelpmonthsClients $hmClient)
    {
        $this->hmClients->removeElement($hmClient);
    }

    /**
     * Get hmClients
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHmClients()
    {
        return $this->hmClients;
    }
}
