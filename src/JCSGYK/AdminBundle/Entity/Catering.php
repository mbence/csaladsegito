<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Catering
 *
 * @ORM\Table(name="catering")
 * @ORM\Entity
 */
class Catering
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
     * @ORM\OneToOne(targetEntity="Client", inversedBy="catering")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var Club
     *
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="clientcaterings")
     * @ORM\JoinColumn(name="club_id", referencedColumnName="id")
     */
    private $club;

    /**
     * @var string
     *
     * @ORM\Column(name="subscriptions", type="text", nullable=true)
     */
    private $subscriptions;

    /**
     * @var integer
     *
     * @ORM\Column(name="menu", type="smallint", nullable=true)
     */
    private $menu;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_single", type="boolean", nullable=true)
     */
    private $isSingle;

    /**
     * @var integer
     *
     * @ORM\Column(name="income", type="integer", nullable=true)
     */
    private $income;

    /**
     * @var integer
     *
     * @ORM\Column(name="discount", type="integer", nullable=true)
     */
    private $discount;



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
     * Set clubId
     *
     * @param integer $clubId
     *
     * @return Catering
     */
    public function setClubId($clubId)
    {
        $this->clubId = $clubId;

        return $this;
    }

    /**
     * Get clubId
     *
     * @return integer
     */
    public function getClubId()
    {
        return $this->clubId;
    }

    /**
     * Set subscriptions
     *
     * @param string $subscriptions
     *
     * @return Catering
     */
    public function setSubscriptions($subscriptions)
    {
        $this->subscriptions = json_encode($subscriptions);

        return $this;
    }

    /**
     * Get subscriptions
     *
     * @return string
     */
    public function getSubscriptions()
    {
        return json_decode($this->subscriptions, true);
    }

    /**
     * Set menu
     *
     * @param integer $menu
     *
     * @return Catering
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;

        return $this;
    }

    /**
     * Get menu
     *
     * @return integer
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * Set isSingle
     *
     * @param boolean $isSingle
     *
     * @return Catering
     */
    public function setIsSingle($isSingle)
    {
        $this->isSingle = $isSingle;

        return $this;
    }

    /**
     * Get isSingle
     *
     * @return boolean
     */
    public function getIsSingle()
    {
        return $this->isSingle;
    }

    /**
     * Set income
     *
     * @param integer $income
     *
     * @return Catering
     */
    public function setIncome($income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income
     *
     * @return integer
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     *
     * @return Catering
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return integer
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set client
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     *
     * @return Catering
     */
    public function setClient(\JCSGYK\AdminBundle\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \JCSGYK\AdminBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set club
     *
     * @param \JCSGYK\AdminBundle\Entity\Club $club
     *
     * @return Catering
     */
    public function setClub(\JCSGYK\AdminBundle\Entity\Club $club = null)
    {
        $this->club = $club;

        return $this;
    }

    /**
     * Get club
     *
     * @return \JCSGYK\AdminBundle\Entity\Club
     */
    public function getClub()
    {
        return $this->club;
    }
}
