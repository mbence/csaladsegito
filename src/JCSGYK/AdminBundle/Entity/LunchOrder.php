<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LunchOrder
 *
 * @ORM\Table(name="lunch_order")
 * @ORM\Entity
 */
class LunchOrder
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
     * @ORM\Column(name="date", type="date", nullable=true)
     */
    private $date;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="summary", type="text", nullable=true)
     */
    private $summary;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="blob", nullable=true)
     */
    private $file;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="lunchorders")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $creator;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;


}

