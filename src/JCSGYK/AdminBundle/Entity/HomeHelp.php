<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * HomeHelp
 *
 * @ORM\Table(name="home_help")
 * @ORM\Entity(repositoryClass="JCSGYK\AdminBundle\Entity\HomeHelpRepository")
 */
class HomeHelp
{
    /** Home help type (Central) */
    const HELP = 0;
    /** Visit type (Clubs) */
    const VISIT = 1;

    const ACTIVE = 1;
    const PAUSED = -1;
    const CLOSED = 0;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Client", inversedBy="homehelp")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var Club
     *
     * @ORM\ManyToOne(targetEntity="Club")
     * @ORM\JoinColumn(name="club_id", referencedColumnName="id")
     */
    private $club;

    /**
     * @var integer
     *
     * @ORM\Column(name="social_worker", type="integer", nullable=true)
     */
    private $socialWorker;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

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
     * @var \DateTime
     *
     * @ORM\Column(name="discount_from", type="date", nullable=true)
     */
    private $discountFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="discount_to", type="date", nullable=true)
     */
    private $discountTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="agreement_from", type="date", nullable=true)
     */
    private $agreementFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="agreement_to", type="date", nullable=true)
     */
    private $agreementTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="paused_from", type="date", nullable=true)
     */
    private $pausedFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="paused_to", type="date", nullable=true)
     */
    private $pausedTo;

    /**
     * @var string
     *
     * @ORM\Column(name="services", type="json_array", nullable=true)
     */
    private $services;

    /**
     * @var boolean
     *
     * @ORM\Column(name="warning_system", type="boolean", nullable=true)
     */
    private $warningSystem;

    /**
     * @var integer
     *
     * @ORM\Column(name="handicap", type="json_array", nullable=true)
     */
    private $handicap;

    /**
     * @var boolean
     *
     * @ORM\Column(name="inpatient", type="boolean", nullable=true)
     */
    private $inpatient;

    /**
     * @var integer
     *
     * @ORM\Column(name="hours", type="smallint", nullable=true)
     */
    private $hours;

    /**
     * @var integer
     *
     * @ORM\Column(name="balance", type="integer", nullable=true)
     */
    private $balance;

    /**
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
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
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id")
     */
    private $modifier;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $date = new \DateTime('today');
        if (date('H') >= 10) {
            $date->modify('+1Day');
        }
        $metadata->addPropertyConstraint('agreementTo', new Assert\GreaterThan(array('value' => $date)));
        $metadata->addPropertyConstraint('pausedFrom', new Assert\GreaterThan(array('value' => $date)));
        $metadata->addPropertyConstraint('pausedTo', new Assert\GreaterThan(array('value' => $date)));
    }

    /**
     * Get the list of fields for change tracking
     * @return array of field names
     */
    public function getHistoryFields()
    {
        return ['club', 'socialWorker', 'status', 'income', 'discount', 'discountFrom', 'discountTo', 'agreementFrom', 'agreementTo', 'pausedFrom', 'pausedTo', 'services', 'warningSystem', 'inpatient', 'handicap', 'hours'];
    }

    /**
     * Returns the required information for the entity history
     * Usage: $this->container->get('jcs.ds')->getinfo($entity);
     * @return array
     */
    public function getHistoryInfo()
    {
        return [
            'default' => [
                'hash' => 'Client',
                'id'   => $this->client->getId(),
                'data' => null
            ],

            // the final hash will be generated by the DataStore::getHistoryInfo() function
        ];
    }

    /**
     * Normalize the date parameter
     * @param mixed $date
     * @return \DateTime
     */
    private function fixDate($date)
    {
        if (empty($date)) {
            $date = new \DateTime('today');
        } elseif (!$date instanceof \DateTime) {
            $date = new \DateTime($date);
        }

        return $date;
    }

    /**
     * Is this catering active?
     * @param mixed $date
     * @return boolean
     */
    public function isActive($date = null)
    {
        return $this->getStatus($date) == self::ACTIVE ? true : false;
    }

    /**
     * Do we have an agreement?
     * @param mixed $date
     * @return boolean
     */
    public function hasAgreement($date = null)
    {
        return $this->getStatus($date) != self::CLOSED ? true : false;
    }

    /**
     * Get status
     * @param mixed $date
     * @return integer
     */
    public function getStatus($date = null)
    {
        $date = $this->fixDate($date);

        // check for agreement
        if ((empty($this->getAgreementFrom()) || $date < $this->getAgreementFrom()) || (!empty($this->getAgreementTo()) && $date > $this->getAgreementTo())) {
            return self::CLOSED;
        }

        // check for pause
        if ((!empty($this->getPausedFrom()) && $date >= $this->getPausedFrom()) && (empty($this->getPausedTo()) || $date <= $this->getPausedTo())) {
            return self::PAUSED;
        }

        return self::ACTIVE;
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
     * Set socialWorker
     *
     * @param integer $socialWorker
     *
     * @return HomeHelp
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
     * Set income
     *
     * @param integer $income
     *
     * @return HomeHelp
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
     * @return HomeHelp
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
     * Set discountFrom
     *
     * @param \DateTime $discountFrom
     *
     * @return HomeHelp
     */
    public function setDiscountFrom($discountFrom)
    {
        $this->discountFrom = $discountFrom;

        return $this;
    }

    /**
     * Get discountFrom
     *
     * @return \DateTime
     */
    public function getDiscountFrom()
    {
        return $this->discountFrom;
    }

    /**
     * Set discountTo
     *
     * @param \DateTime $discountTo
     *
     * @return HomeHelp
     */
    public function setDiscountTo($discountTo)
    {
        $this->discountTo = $discountTo;

        return $this;
    }

    /**
     * Get discountTo
     *
     * @return \DateTime
     */
    public function getDiscountTo()
    {
        return $this->discountTo;
    }

    /**
     * Set agreementFrom
     *
     * @param \DateTime $agreementFrom
     *
     * @return HomeHelp
     */
    public function setAgreementFrom($agreementFrom)
    {
        $this->agreementFrom = $agreementFrom;

        return $this;
    }

    /**
     * Get agreementFrom
     *
     * @return \DateTime
     */
    public function getAgreementFrom()
    {
        return $this->agreementFrom;
    }

    /**
     * Set agreementTo
     *
     * @param \DateTime $agreementTo
     *
     * @return HomeHelp
     */
    public function setAgreementTo($agreementTo)
    {
        $this->agreementTo = $agreementTo;

        return $this;
    }

    /**
     * Get agreementTo
     *
     * @return \DateTime
     */
    public function getAgreementTo()
    {
        return $this->agreementTo;
    }

    /**
     * Set services
     *
     * @param array $services
     *
     * @return HomeHelp
     */
    public function setServices($services)
    {
        $this->services = $services;

        return $this;
    }

    /**
     * Get services
     *
     * @return array
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Set warningSystem
     *
     * @param boolean $warningSystem
     *
     * @return HomeHelp
     */
    public function setWarningSystem($warningSystem)
    {
        $this->warningSystem = $warningSystem;

        return $this;
    }

    /**
     * Get warningSystem
     *
     * @return boolean
     */
    public function getWarningSystem()
    {
        return $this->warningSystem;
    }

    /**
     * Set hours
     *
     * @param integer $hours
     *
     * @return HomeHelp
     */
    public function setHours($hours)
    {
        $this->hours = $hours;

        return $this;
    }

    /**
     * Get hours
     *
     * @return integer
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * Set creator
     *
     * @param \JCSGYK\AdminBundle\Entity\User $creator
     * @return Client
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return HomeHelp
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
     * Set modifier
     *
     * @param \JCSGYK\AdminBundle\Entity\User $modifier
     * @return Client
     */
    public function setModifier(\JCSGYK\AdminBundle\Entity\User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return \JCSGYK\AdminBundle\Entity\User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return HomeHelp
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
     * Set client
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     *
     * @return HomeHelp
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
     * @return HomeHelp
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

    /**
     * Set balance
     *
     * @param integer $balance
     *
     * @return Homehelp
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance
     *
     * @return integer
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Check if this client has an active discount
     * @param \DateTime $date
     * @return bool
     */
    public function discountIsActive(\DateTime $date = null)
    {
        if (is_null($date)) {
            $date = new \DateTime('today');
        }

        return !empty($this->getDiscount()) && (empty($this->getDiscountFrom()) && empty($this->getDiscountFrom()) || $this->getDiscountFrom() <= $date && $this->getDiscountTo() >= $date);
    }

    /**
     * Set handicap
     *
     * @param array $handicap
     *
     * @return Homehelp
     */
    public function setHandicap($handicap)
    {
        $this->handicap = $handicap;

        return $this;
    }

    /**
     * Get handicap
     *
     * @return array
     */
    public function getHandicap()
    {
        return $this->handicap;
    }

    /**
     * Set inpatient
     *
     * @param boolean $inpatient
     *
     * @return HomeHelp
     */
    public function setInpatient($inpatient)
    {
        $this->inpatient = $inpatient;

        return $this;
    }

    /**
     * Get inpatient
     *
     * @return boolean
     */
    public function getInpatient()
    {
        return $this->inpatient;
    }

    /**
     * Set pausedFrom
     *
     * @param \DateTime $pausedFrom
     *
     * @return HomeHelp
     */
    public function setPausedFrom($pausedFrom)
    {
        $this->pausedFrom = $pausedFrom;

        return $this;
    }

    /**
     * Get pausedFrom
     *
     * @return \DateTime
     */
    public function getPausedFrom()
    {
        return $this->pausedFrom;
    }

    /**
     * Set pausedTo
     *
     * @param \DateTime $pausedTo
     *
     * @return HomeHelp
     */
    public function setPausedTo($pausedTo)
    {
        $this->pausedTo = $pausedTo;

        return $this;
    }

    /**
     * Get pausedTo
     *
     * @return \DateTime
     */
    public function getPausedTo()
    {
        return $this->pausedTo;
    }
}
