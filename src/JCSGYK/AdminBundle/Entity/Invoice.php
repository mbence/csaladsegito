<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoice
 *
 * @ORM\Table(name="invoice", indexes={@ORM\Index(name="company_id", columns={"company_id", "date"})})
 * @ORM\Entity
 */
class Invoice
{
    /**
     * Invoice status constants
     */
    /** Just created the record, writing for the transfer to EcoSTAT */
    const READY_TO_SEND = 1;
    /** Invoice is sent, and printed, open for payments */
    const OPEN = 2;
    /** All payments are done, closed */
    const CLOSED = 3;
    /** Invoice cancelled */
    const CANCELLED = -1;

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
     * @var \Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="invoices")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

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
     * @ORM\Column(name="cancel_id", type="integer", nullable=false)
     */
    private $cancelId;

    /**
     * @var string
     *
     * @ORM\Column(name="items", type="text", nullable=true)
     */
    private $items;

    /**
     * @var string
     *
     * @ORM\Column(name="days", type="text", nullable=true)
     */
    private $days;

    /**
     * @var string
     *
     * @ORM\Column(name="changes", type="text", nullable=true)
     */
    private $changes;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=true)
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="balance", type="integer", nullable=true)
     */
    private $balance;

    /**
     * @var string
     *
     * @ORM\Column(name="payments", type="text", nullable=true)
     */
    private $payments;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="User")
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
     * @var integer
     *
     * @ORM\Column(name="invoicetype", type="integer", nullable=true)
     */
    private $invoicetype;

    /**
     * Get the list of fields for change tracking
     * @return array of field names
     */
    public function getHistoryFields()
    {
        return ['payments', 'status'];
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
            'insert' => false,

            // the final hash will be generated by the DataStore::getHistoryInfo() function
        ];
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
     * @return Invoice
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
     * Set items
     *
     * @param string $items
     *
     * @return Invoice
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Get items
     *
     * @return string
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Invoice
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set balance
     *
     * @param integer $balance
     *
     * @return Invoice
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
     * Set payments
     *
     * @param string $payments
     *
     * @return Invoice
     */
    public function setPayments($payments)
    {
        $this->payments = json_encode($payments);

        return $this;
    }

    /**
     * Get payments
     *
     * @return string
     */
    public function getPayments()
    {
        return json_decode($this->payments, true);
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Invoice
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Invoice
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
     * Set client
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     *
     * @return Invoice
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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Invoice
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
     * @return Invoice
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
     * Set creator
     *
     * @param \JCSGYK\AdminBundle\Entity\User $creator
     *
     * @return Invoice
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
     * Set days
     *
     * @param string $days
     *
     * @return Invoice
     */
    public function setDays($days)
    {
        $this->days = $days;

        return $this;
    }

    /**
     * Get days
     *
     * @return string
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Set changes
     *
     * @param string $changes
     *
     * @return Invoice
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;

        return $this;
    }

    /**
     * Get changes
     *
     * @return string
     */
    public function getChanges()
    {
        return $this->changes;
    }

    public function isOpen()
    {
        return self::OPEN == $this->getStatus();
    }


    public function addPayment($amount)
    {
        if (is_numeric($amount)) {
            if ($this->getBalance() + $amount > $this->getAmount()) {
                return -1;
            }

            $payments = $this->getPayments();
            $payments[] = [date('Y-m-d'), $amount];
            $this->setPayments($payments);
            $this->updateBalance();
        }
    }

    public function updateBalance()
    {
        $balance = 0;
        $payments = $this->getPayments();
        foreach ($payments as $payment) {
            $balance += $payment[1];
        }

        $this->setBalance($balance);
    }

    public function updateStatus()
    {
        if (self::OPEN == $this->getStatus()) {
            if ($this->getAmount() == $this->getBalance()) {
                $this->setStatus(self::CLOSED);
            }
        }
    }

    /**
     * Check if this invoice can be cancelled
     */
    public function cancellable()
    {
        return  $this->status == self::OPEN && empty($this->payments);
    }

    /**
     * Set cancelId
     *
     * @param integer $cancelId
     *
     * @return Invoice
     */
    public function setCancelId($cancelId)
    {
        $this->cancelId = $cancelId;

        return $this;
    }

    /**
     * Get cancelId
     *
     * @return integer
     */
    public function getCancelId()
    {
        return $this->cancelId;
    }

    /**
     * Set invoicetype
     *
     * @param integer $invoicetype
     *
     * @return Invoice
     */
    public function setInvoicetype($invoicetype)
    {
        $this->invoicetype = $invoicetype;

        return $this;
    }

    /**
     * Get invoicetype
     *
     * @return integer
     */
    public function getInvoicetype()
    {
        return $this->invoicetype;
    }
}
