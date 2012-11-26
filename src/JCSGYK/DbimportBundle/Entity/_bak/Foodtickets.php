<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Foodtickets
 *
 * @ORM\Table(name="FoodTickets")
 * @ORM\Entity
 */
class Foodtickets
{
    /**
     * @var integer
     *
     * @ORM\Column(name="FoodTicket_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $foodticketId;

    /**
     * @var integer
     *
     * @ORM\Column(name="TicketNum", type="integer", nullable=false)
     */
    private $ticketnum;

    /**
     * @var integer
     *
     * @ORM\Column(name="OpCode", type="integer", nullable=false)
     */
    private $opcode;



    /**
     * Get foodticketId
     *
     * @return integer 
     */
    public function getFoodticketId()
    {
        return $this->foodticketId;
    }

    /**
     * Set ticketnum
     *
     * @param integer $ticketnum
     * @return Foodtickets
     */
    public function setTicketnum($ticketnum)
    {
        $this->ticketnum = $ticketnum;
    
        return $this;
    }

    /**
     * Get ticketnum
     *
     * @return integer 
     */
    public function getTicketnum()
    {
        return $this->ticketnum;
    }

    /**
     * Set opcode
     *
     * @param integer $opcode
     * @return Foodtickets
     */
    public function setOpcode($opcode)
    {
        $this->opcode = $opcode;
    
        return $this;
    }

    /**
     * Get opcode
     *
     * @return integer 
     */
    public function getOpcode()
    {
        return $this->opcode;
    }
}