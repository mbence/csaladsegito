<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HomehelpMonthsClients
 *
 * @ORM\Table(name="homehelp_months_clients")
 * @ORM\Entity
 */
class HomehelpMonthsClients
{
    /**
     * @var integer
     *
     * @ORM\Column(name="homehelp_month_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $homehelpMonthId;

    /**
     * @var integer
     *
     * @ORM\Column(name="client_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $clientId;



    /**
     * Set homehelpMonthId
     *
     * @param integer $homehelpMonthId
     *
     * @return HomehelpMonthsClients
     */
    public function setHomehelpMonthId($homehelpMonthId)
    {
        $this->homehelpMonthId = $homehelpMonthId;

        return $this;
    }

    /**
     * Get homehelpMonthId
     *
     * @return integer
     */
    public function getHomehelpMonthId()
    {
        return $this->homehelpMonthId;
    }

    /**
     * Set clientId
     *
     * @param integer $clientId
     *
     * @return HomehelpMonthsClients
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get clientId
     *
     * @return integer
     */
    public function getClientId()
    {
        return $this->clientId;
    }
}
