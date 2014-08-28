<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatFile
 *
 * @ORM\Table(name="stat_file")
 * @ORM\Entity
 */
class StatFile
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
     * @ORM\ManyToOne(targetEntity="StatArchive", inversedBy="files")
     * @ORM\JoinColumn(name="stat_archive_id", referencedColumnName="id")
     */
    private $statArchive;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint", nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="blob", nullable=true)
     */
    private $file;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

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
     * Set type
     *
     * @param integer $type
     *
     * @return StatArchive
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
     * Set data
     *
     * @param string $data
     *
     * @return StatFile
     */
    public function setData($data)
    {
        $this->data = json_encode($data);

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return json_decode($this->data, true);
    }

    /**
     * Set file
     *
     * @param string $file
     *
     * @return StatFile
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return StatFile
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
     * Set statArchive
     *
     * @param \JCSGYK\AdminBundle\Entity\StatArchive $statArchive
     *
     * @return StatFile
     */
    public function setStatArchive(\JCSGYK\AdminBundle\Entity\StatArchive $statArchive = null)
    {
        $this->statArchive = $statArchive;

        return $this;
    }

    /**
     * Get statArchive
     *
     * @return \JCSGYK\AdminBundle\Entity\StatArchive
     */
    public function getStatArchive()
    {
        return $this->statArchive;
    }
}
