<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MonthlyClosing
 *
 * @ORM\Table(name="monthly_closing", indexes={@ORM\Index(name="date", columns={"company_id", "date"})})
 * @ORM\Entity
 */
class MonthlyClosing
{
    /** Status constants */
    const RUNNING   = 1;
    const SUCCESS   = 2;
    const ERROR     = -1;

    /** Closing Types */
    const MONTHLY  = 1;
    const DAILY    = 2;
    const HOMEHELP = 3;

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
     * @ORM\Column(name="start_date", type="date", nullable=false)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="date", nullable=false)
     */
    private $endDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="summary", type="text", nullable=true)
     */
    private $summary;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="closings")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var blob
     *
     * @ORM\Column(name="files", type="blob", nullable=true)
     */
    private $files;

    /**
     * @var integer
     *
     * @ORM\Column(name="closingtype", type="integer", nullable=true)
     */
    private $closingtype;


    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
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
     * @return MonthlyClosing
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
     * Set status
     *
     * @param integer $status
     *
     * @return MonthlyClosing
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
     * Set summary
     *
     * @param string $summary
     *
     * @return MonthlyClosing
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return MonthlyClosing
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
     * Set creator
     *
     * @param \JCSGYK\AdminBundle\Entity\User $creator
     *
     * @return MonthlyClosing
     */
    public function setCreator(\JCSGYK\AdminBundle\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return MonthlyClosing
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return MonthlyClosing
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set files
     *
     * @param string $files
     *
     * @return MonthlyClosing
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get files
     *
     * @return string 
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set closingtype
     *
     * @param integer $closingtype
     *
     * @return MonthlyClosing
     */
    public function setClosingtype($closingtype)
    {
        $this->closingtype = $closingtype;

        return $this;
    }

    /**
     * Get closingtype
     *
     * @return integer
     */
    public function getClosingtype()
    {
        return $this->closingtype;
    }
}
