<?php

namespace JCSGYK\AdminBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use JCSGYK\AdminBundle\Services\DataStore;

use JCSGYK\AdminBundle\Entity\StatArchive;
use JCSGYK\AdminBundle\Entity\StatFile;
use JCSGYK\AdminBundle\Entity\Club;
use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\HomeHelp;
use JCSGYK\AdminBundle\Entity\ClientOrder;
use JCSGYK\AdminBundle\Entity\Archive;

/**
 * Statistics Service
 */
class StatArchiveService
{
    /** Service container */
    private $container;

    /** @var DataStore ds */
    private $ds;
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;
    /**
     * @var JCSGYK\AdminBundle\Twig\AdminExtension
     */
    private $ae;

    /** Command output Interface
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /** Process summary text */
    private $summary = '';

    private $incomeHeaders = [
        0 => '28 500 alatt',
        1 => '57 000-ig',
        2 => '85 000-ig',
        3 => '114 000-ig',
        4 => '114 000 felett'
    ];
    private $incomeRanges = [
        0 => [0, 28500],
        1 => [28501, 57000],
        2 => [57001, 85000],
        3 => [85001, 114000],
        4 => [114001, 99999999],
    ];
    private $ageHeaders = [
        0 => '18 év alatt',
        1 => '18-39 évig',
        2 => '40-59 évig',
        3 => '60-64 évig',
        4 => '65-69 évig',
        5 => '70-74 évig',
        6 => '75-79 évig',
        7 => '80-89 évig',
        8 => '90 év felett',
    ];
    private $ageRanges = [
        0 =>  [0, 18],
        1 =>  [18, 39],
        2 =>  [40, 59],
        3 =>  [60, 64],
        4 =>  [65, 69],
        5 =>  [70, 74],
        6 =>  [75, 79],
        7 =>  [80, 89],
        8 =>  [90, 999],
    ];

    private $holidays = [];

    private $clientCosts = [];

    /** Constructor
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->ds        = $this->container->get('jcs.ds');
        $this->em        = $this->container->get('doctrine')->getManager();
        $this->ae        = $this->container->get('jcs.twig.adminextension');
    }

    /**
     * Output text
     * @param $text
     */
    private function output($text)
    {
        $this->summary .= $text . "\n";

        if (!empty($this->output)) {
            $this->output->writeln($text);
        }
    }

    /**
     * Start the stat process
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param text $month if set, the stat will generate for this month
     * @param null $stat if set, only this stat will run, otherwise all
     */
    public function run(OutputInterface $output = null, $month = null, $stat = null)
    {
        $this->output = $output;

        // set the start / end dates
        if (is_null($month)) {
            $start = new \DateTime('first day of last month');
            $end = new \DateTime('last day of last month');
        } else {
            $m = new \DateTime($month);
            $start = new \DateTime($m->format('Y-m-01'));
            $end = new \DateTime($m->format('Y-m-t'));
        }

        $created_at = new \DateTime();

        $this->output('Statisztika');
        $this->output(sprintf("%s - %s \n", $start->format('Y-m-d'), $end->format('Y-m-d')));
        $this->output(sprintf("%s: Indítva", $created_at->format('H:i:s')));
        // add 1 day to the end date to include all data created on that day
        $end->modify('+1day');

        $stats_to_run = [];

        // get the client type list for this company
        $client_types = $this->ds->getClientTypeNames(true);
        foreach ($client_types as $client_type => $type_name) {
            // get the statistics for the client types
            $stat_list = $this->ds->getStatsForType($client_type);

            foreach ($stat_list as $stat_id => $stat_name) {
                $stats_to_run[$stat_id] = $stat_name;
            }
        }
        // if a $stat is provided
        if (!is_null($stat)) {
            if (!isset($stats_to_run[$stat])) {
                // problem
                throw new BadRequestHttpException('Invalid Stat Id');
            }
            // clear the other stat ids
            $stats_to_run = [$stat => $stats_to_run[$stat]];
        }

        foreach ($stats_to_run as $stat_id => $stat_name) {
            $this->output(sprintf("%s: %s (%s)", $created_at->format('H:i:s'), $stat_name, $stat_id));

            // run the stat
            $res = $this->createStat($stat_id, $start, $end);


            if (!empty($res)) {
                $this->output(sprintf("%s: Adatok elmentve (id: %s)", $created_at->format('H:i:s'), $res->getId()));
            }
            else {
                $this->output(sprintf("%s: HIBA! Üres adatok", $created_at->format('H:i:s')));
            }
        }
    }

    /**
     * Creates a StatArchive record, and starts the stat generators
     * @param int $type
     * @param \DateTime $start
     * @param \DateTime $end
     * @return StatArchive
     */
    private function createStat($type, \DateTime $start, \DateTime $end)
    {
        $company_id = $this->ds->getCompanyId();

        // save the stat
        $sa = new StatArchive();
        $sa->setCompanyId($company_id);
        $sa->setStart($start);
        $sa->setEnd($end);
        $sa->setType($type);
        $this->em->persist($sa);

        // find the stat variants
        $vars = $this->getVariants($type);

        foreach ($vars as $var) {
            $res = $this->runStat($type, $var, $start, $end);

            if (!empty($res)) {
                // save the results
                $this->saveFile($sa, $res['type'], $res['data'], $res['file']);
            }
        }

        $this->em->flush();

        return $sa;
    }

    /**
     * Returns a list of the required statistic variants (Clubs) for a stat type
     *
     * @param int $type
     * @return array
     */
    private function getVariants($type)
    {
        $company_id = $this->ds->getCompanyId();
        $re = [];

        // for catering types, we need the clubs
        if (in_array($type, [401, 402])) {
            $re = $this->em->getRepository('JCSGYKAdminBundle:Club')->findBy(['companyId' => $company_id, 'isActive' => true], ['name' => 'ASC']);
        }
        else {
            $re = [1];
        }

        return $re;
    }

    /**
     * Decides which stat to run and runs it
     *
     * @param int $type
     * @param mixed $var Variant (eg. Club)
     * @param \DateTime $start
     * @param \DateTime $end
     * @return array
     */
    private function runStat($type, $var, \DateTime $start, \DateTime $end)
    {
        if (401 == $type) {
            return $this->run401($var, $start, $end);
        }
        elseif (402 == $type) {
            return $this->run402($var, $start, $end);
        }
    }

    /**
     * Saves a statFile
     *
     * @param \JCSGYK\AdminBundle\Entity\StatArchive $sa
     * @param $type
     * @param array $data
     * @param $file
     * @return \JCSGYK\AdminBundle\Entity\StatFile
     */
    private function saveFile(StatArchive $sa, $type, array $data, $file)
    {
        $sf = (new StatFile())
            ->setStatArchive($sa)
            ->setType($type)
            ->setData($data)
            ->setFile($file)
        ;

        $this->em->persist($sf);

        return $sf;
    }

    /**
     * Runs the Catering stats
     *
     * @param Club $club
     * @param \DateTime $start
     * @param \DateTime $end
     * @return array
     */
    private function run401(Club $club, \DateTime $start, \DateTime $end)
    {
        // prepare the data arra
        $data = $this->prepare401Data($club, $start);
        // prepare holidays
        $this->prepareHolidays($start, $end);

        // get the invoices from db
        $invoices = $this->get401Invoices($club, $start, $end);

        // get accounted balance
        $data = $this->get401AccountedBalance($invoices, $data);

        // get all affected clients
        $clients = $this->get401Clients($club, $start, $end);

        foreach ($clients as $client) {
            $data = $this->get401BaseData($client, $start, $end, $data);
        }

        // get the client orders
        $orders = $this->get401Orders($club, $start, $end, false);
        foreach ($orders as $order) {
            $cost = $this->get401ClientCost($order, $invoices);
            $data = $this->get401OrderData($order, $cost, $data);
        }

        // sum up some fields
        $data = $this->get401Sums($data);

        $template_file = __DIR__ . '/../Resources/public/reports/catering_stats.xlsx';

        return [
            'type' => !empty($club) ? $club->getId() : null,
            'data' => $data,
            'file' => $this->container->get('jcs.docx')->make($template_file, $data, null)
        ];
    }

    /**
     * Prepare the data array
     * @param Club $club
     * @param \DateTime $month
     * @return array
     */
    private function prepare401data(Club $club, \DateTime $month)
    {
        $data = [
            'ca.cim'          => sprintf('%s havi EBÉD statisztika', $this->ae->formatDate($month, 'ym')),
            'ca.klub'         => empty($club) ? '' : sprintf(' (%s)', $club->getName()),
            'sp.datum'        => $this->ae->formatDate(new \DateTime('today')),
            'cnum.start'      => 0,
            'cnum.new'        => 0,
            'cnum.all'        => 0,
            'cnum.active'     => [],
            'cnum.archived'   => 0,
            'cnum.paused'     => 0,
            'cnum.end'        => 0,
            'cnum.man'        => 0,
            'cnum.woman'      => 0,
            'inv.days'        => 0,
            'inv.discweek'    => 0,
            'inv.discweekcli' => [],
            'inv.discend'    => 0,
            'inv.discendcli' => [],
            'inv.payweek'     => 0,
            'inv.payweekcli'  => [],
            'inv.payend'      => 0,
            'inv.payendcli'   => [],
            'inv.sum'         => 0,
            'inv.acc'         => 0,
            'inv.def'         => 0,
            'blocks'          => [],
        ];
        // comfort
        $data['blocks']['comfort_headers'] = $this->ds->getGroup(200);
        foreach ($this->ds->getGroup(200) as $c => $v) {
            $data['blocks']['comfort'][$c] = 0;
        }

        // ownership
        $data['blocks']['ownership_headers'] = $this->ds->getGroup(204);
        foreach ($this->ds->getGroup(204) as $o => $v) {
            $data['blocks']['ownership'][$o] = 0;
        }
        // ranges
        $data['blocks']['income_headers'] = $this->incomeHeaders;
        foreach ($this->incomeRanges as $k => $v) {
            $data['blocks']['income'][$k] = 0;
        }
        $data['blocks']['age_headers'] = $this->ageHeaders;
        foreach ($this->ageRanges as $k => $v) {
            $data['blocks']['age_2'][$k] = ['all' => 0, 'active' => 0];
            $data['blocks']['age_1'][$k] = ['all' => 0, 'active' => 0];
        }

        return $data;
    }

    /**
     * Read the holidays for this period
     * Codes: 1 - Holiday, 2 - Working weekend, 3 - Rest day
     * @param \DateTime $start
     * @param \DateTime $end
     */
    private function prepareHolidays(\DateTime $start, \DateTime $end)
    {
        $this->holidays = $this->ds->getHolidays($start->format('Y-m-d'), $end->format('Y-m-d'));
    }

    /**
     * Is this date a weekend? Also check the holidays table from the options
     * @param \DateTime $date
     * @return bool
     */
    private function weekendOrWorkday(\DateTime $date)
    {
        $weekend = ($date->format('N') > 5);
        $isoDate = $date->format('Y-m-d');
        if (isset($this->holidays[$isoDate])) {
            $weekend = in_array($this->holidays[$isoDate], [1, 3]);
        }

        return $weekend;
    }


    /**
     * Get the invoices for 401 (Catering stat)
     * @param Club $club
     * @param \DateTime $start
     * @param \DateTime $end
     * @return Invoice[]
     */
    private function get401Invoices(Club $club, \DateTime $start, \DateTime $end)
    {
        $dql = "SELECT c, a, i FROM JCSGYKAdminBundle:Invoice i LEFT JOIN i.client c LEFT JOIN c.catering a "
                . "WHERE c.companyId = :company_id AND i.startDate >= :month_start AND i.endDate < :month_end AND i.invoicetype IN (:types)";
        if (!empty($club)) {
            $dql .= ' AND a.club = :club';
        }

        $query = $this->em->createQuery($dql)
            ->setParameter('company_id', $this->ds->getCompanyId())
            ->setParameter('month_start', $start->format('Y-m-d'))
            ->setParameter('month_end', $end->format('Y-m-d'))
            ->setParameter('types', [Invoice::MONTHLY, Invoice::DAILY])
        ;
        if (!empty($club)) {
            $query->setParameter('club', $club);
        }

        $res = $query->getResult();

        $invoices = [];
        foreach ($res as $invoice) {
            $client_id = $invoice->getClient()->getId();
            if (empty($invoices[$client_id])) {
                $invoices[$client_id] = $invoice;
            }
        }

        return $invoices;
    }

    private function get401AccountedBalance($invoices, $data)
    {
        foreach ($invoices as $invoice) {
            $data['inv.acc'] += $invoice->getBalance();
        }

        return $data;
    }

    /**
     * Get clients for 401 who have an active catering record in this period
     * @param Club $club
     * @param \DateTime $end
     */
    private function get401Clients(Club $club, \DateTime $start, \DateTime $end)
    {
        $dql = "SELECT c, a FROM JCSGYKAdminBundle:Client c JOIN c.catering a "
                . "WHERE c.companyId = :company_id AND c.createdAt < :end AND (a.agreementTo IS NULL OR a.agreementTo >= :start)";
        if (!empty($club)) {
            $dql .= ' AND a.club = :club';
        }

        $query = $this->em->createQuery($dql)
            ->setParameter('company_id', $this->ds->getCompanyId())
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
        ;
        if (!empty($club)) {
            $query->setParameter('club', $club);
        }

        return $query->getResult();
    }

        /**
     * Get client orders for 401
     * @param \JCSGYK\AdminBundle\Services\Cliub $club
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool $group GROUP BY ?
     */
    private function get401Orders(Club $club, \DateTime $start, \DateTime $end, $group = true)
    {
        $dql = "SELECT o, c, a FROM JCSGYKAdminBundle:ClientOrder o JOIN o.client c JOIN c.catering a "
                . "WHERE c.companyId = :company_id AND o.date >= :month_start AND o.date < :month_end ";
        if (!empty($club)) {
            $dql .= ' AND a.club = :club';
        }
        if ($group) {
            $dql .= ' GROUP BY c.id';
        }

        $query = $this->em->createQuery($dql)
            ->setParameter('company_id', $this->ds->getCompanyId())
            ->setParameter('month_start', $start->format('Y-m-d'))
            ->setParameter('month_end', $end->format('Y-m-d'));
        if (!empty($club)) {
            $query->setParameter('club', $club);
        }

        return $query->getResult();
    }

    /**
     * Get base stat data for 401
     * @param Client $client
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $data
     * @return array
     */
    private function get401BaseData(Client $client, \DateTime $start, \DateTime $end, $data)
    {
        $params = $client->getParams();
        $catering = $client->getCatering();

        // month start - new/old clients
        if ($catering->getAgreementFrom() >= $start) {
            $data['cnum.new']++;
        } else {
            $data['cnum.start']++;
        }
        // month end - closed clients
        if (!$catering->hasAgreement($end)) {
            // it is not real archived, it counts only clients without active agreement
            $data['cnum.archived']++;
        } elseif ($catering->getStatus($start) == $catering::PAUSED) {
            // count paused catering too
            $data['cnum.paused']++;
            $data['cnum.end']++;
        } else {
            $data['cnum.end']++;
        }

        // comfort
        if (!empty($params[200])) {
            if (!isset($data['blocks']['comfort'][$params[200]])) {
                $data['blocks']['comfort'][$params[200]] = 0;
            }
            $data['blocks']['comfort'][$params[200]]++;
        }
        // ownership
        if (!empty($params[204])) {
            if (!isset($data['blocks']['ownership'][$params[204]])) {
                $data['blocks']['ownership'][$params[204]] = 0;
            }
            $data['blocks']['ownership'][$params[204]]++;
        }
        // income
        $k = $this->getRangeKey($client->getCatering()->getIncome(), $this->incomeRanges);
        if (false !== $k) {
            $data['blocks']['income'][$k]++;
        }
        // age
        $now = new \DateTime();
        $gen = $client->getGender();
        $bday = $client->getBirthDate();
        if (!empty($bday)) {
            $age = $now->diff($bday)->format('%y');
            $k = $this->getRangeKey($age, $this->ageRanges);
            if (false !== $k) {
                $data['blocks']['age_' . $gen][$k]['all']++;
                if ($catering->hasAgreement($end)) {
                    $data['blocks']['age_' . $gen][$k]['active']++;
                }
            }
        }
        if ($gen == 1) {
            $data['cnum.man']++;
        }
        else {
            $data['cnum.woman']++;
        }

        return $data;
    }

    /**
     * Find catering cost for a client
     * @param \JCSGYK\AdminBundle\Services\clientOrder $order
     * @param array $invoices
     * @return int
     */
    private function get401ClientCost(ClientOrder $order, $invoices)
    {
        $cost = 0;

        $client_id = $order->getClient()->getId();
        if (!isset($this->clientCosts[$client_id])) {
            $cost = null;
            // new client, get the catering cost
            if (!empty($invoices[$client_id])) {
                $items = json_decode($invoices[$client_id]->getItems(), true);
                $costs = array_keys($items);
                arsort($costs);
                $temp_cost = reset($costs);
                if (is_numeric($temp_cost) && $temp_cost >= 0) {
                    $this->clientCosts[$client_id] = $temp_cost;
                }
                $cost = $temp_cost;
            }

            // no invoice found for this client, calculate the catering cost from the costs table and client income
            if (is_null($cost)) {
                $table = $this->ds->getOption('cateringcosts', $order->getDate()->format('Y-m-d'));
                $cost = $this->container->get('jcs.invoice')->getCostForADay($order->getClient()->getCatering(), $table);
            }
        }
        else {
            $cost = $this->clientCosts[$client_id];
        }

        return $cost;
    }

    private function get401OrderData(ClientOrder $order, $cost, $data)
    {
        $client_id = $order->getClient()->getId();
        if ($order->getOrder() && !$order->getCancel()) {
            $data['inv.days'] ++;
            $data['cnum.active'][$client_id] = 1;

            $weekend = $this->weekendOrWorkday($order->getDate());

            if (empty($cost)) {
                if ($weekend) {
                    $data['inv.discend'] ++;
                    $data['inv.discendcli'][$client_id] = 1;
                }
                else {
                    $data['inv.discweek'] ++;
                    $data['inv.discweekcli'][$client_id] = 1;
                }
            }
            else {
                if ($weekend) {
                    $data['inv.payend'] ++;
                    $data['inv.payendcli'][$client_id] = 1;
                }
                else {
                    $data['inv.payweek'] ++;
                    $data['inv.payweekcli'][$client_id] = 1;
                }
            }

            $data['inv.sum'] += $cost;
        }

        return $data;
    }

    /**
     * Sum up some fields
     * @param array $data
     * @return array
     */
    private function get401Sums($data)
    {
        // get only the client counts
        $data['inv.discweekcli'] = count($data['inv.discweekcli']);
        $data['inv.discendcli'] = count($data['inv.discendcli']);
        $data['inv.payweekcli'] = count($data['inv.payweekcli']);
        $data['inv.payendcli'] = count($data['inv.payendcli']);
        // is active?
        $data['cnum.active'] = count($data['cnum.active']);

        $data['cnum.all'] = $data['cnum.start'] + $data['cnum.new'];

        $data['inv.def'] = $this->ae->formatCurrency($data['inv.sum'] - $data['inv.acc']);
        $data['inv.sum'] = $this->ae->formatCurrency($data['inv.sum']);
        $data['inv.acc'] = $this->ae->formatCurrency($data['inv.acc']);

        // format data for the silly openTBS cell handling method
        $age1 = [];
        $age2 = [];

        foreach ($data['blocks']['age_1'] as $age) {
            $age1[] = $age['active'] . ' (' . $age['all'] . ')';
        }

        foreach ($data['blocks']['age_2'] as $age) {
            $age2[] = $age['active'] . ' (' . $age['all'] . ')';
        }

        $data['blocks']['age_1'] = $age1;
        $data['blocks']['age_2'] = $age2;

        return $data;
    }

    /**
     * Runs the Homehelp stats
     *
     * @param Club $club
     * @param \DateTime $start
     * @param \DateTime $end
     * @return array
     */
    private function run402(Club $club, \DateTime $start, \DateTime $end)
    {
        // prepare the data array
        $data = $this->prepare402Data($club, $start);

        // get all the clients that are created before the end of this period, including the archived clients
        $clients = $this->get402Clients($club, $start, $end);

        foreach ($clients as $client) {
            $data = $this->get402BaseData($client, $start, $end, $data);
        }
        $data['cnum.active'] = $this->get402ActiveClientsNumber($club, $start, $end);

        $data = $this->get402Sums($data);

        if (HomeHelp::HELP == $club->getHomehelptype()) {
            $data = $this->get402InvoiceData($club, $start, $end, $data);
            $tpl_file = 'homehelp_stats.xlsx';
        } else {
            $data = $this->get402VisitData($club, $start, $end, $data);
            $tpl_file = 'clubvisit_stats.xlsx';
        }

        $template_file = __DIR__ . '/../Resources/public/reports/' . $tpl_file;

        return [
            'type' => !empty($club) ? $club->getId() : null,
            'data' => $data,
            'file' => $this->container->get('jcs.docx')->make($template_file, $data, null)
        ];
    }

    /**
     * Prepare the data array
     * @param Club $club
     * @param \DateTime $month
     * @return array
     */
    private function prepare402Data(Club $club, \DateTime $month)
    {
        $type_label = HomeHelp::HELP == $club->getHomehelptype() ? 'GONDOZÁS' : 'LÁTOGATÁS';

        $data = [
            'ca.cim'          => sprintf('%s havi %s statisztika', $this->ae->formatDate($month, 'ym'), $type_label),
            'ca.klub'         => empty($club) ? '' : sprintf(' (%s)', $club->getName()),
            'sp.datum'        => $this->ae->formatDate(new \DateTime('today')),
            'cnum.start'      => 0,
            'cnum.new'        => 0,
            'cnum.all'        => 0,
            'cnum.active'     => 0,
            'cnum.paused'     => 0,
            'cnum.archived'   => 0,
            'cnum.reopened'   => '',
            'cnum.end'        => 0,
            'cnum.man'        => 0,
            'cnum.woman'      => 0,
            'cnum.inpatient'  => 0,
            'inv.hours'       => 0,
            'inv.visits'      => 0,
            'inv.disc'        => 0,
            'inv.disccli'     => [],
            'inv.pay'         => 0,
            'inv.paycli'      => [],
            'inv.sum'         => 0,
            'blocks'          => [],
            'inv.avg05'       => 0,
            'inv.avg12'       => 0,
            'inv.avg34'       => 0,
        ];
        // comfort
        $data['blocks']['comfort_headers'] = $this->ds->getGroup(200);
        foreach ($this->ds->getGroup(200) as $c => $v) {
            $data['blocks']['comfort'][$c] = 0;
        }

        // ownership
        $data['blocks']['ownership_headers'] = $this->ds->getGroup(204);
        foreach ($this->ds->getGroup(204) as $o => $v) {
            $data['blocks']['ownership'][$o] = 0;
        }
        // ranges
        $data['blocks']['income_headers'] = $this->incomeHeaders;
        foreach ($this->incomeRanges as $k => $v) {
            $data['blocks']['income'][$k] = 0;
        }
        $data['blocks']['age_headers'] = $this->ageHeaders;

        foreach ($this->ageRanges as $k => $v) {
            $data['blocks']['age_2'][$k] = 0;
            $data['blocks']['age_1'][$k] = 0;
        }

        // event names
        $data['blocks']['eventcount'] = [];
        $data['blocks']['eventlab'] = [];
//        $data['blocks']['eventmap'] = [];
        foreach ($this->ds->getGroup('club_events') as $c => $v) {
            $data['blocks']['eventcount'][] = 0;
            $data['blocks']['eventlab'][] = $v;
//            $data['blocks']['eventmap'][] = $c;
        }

        return $data;
    }

    /**
     * Get the number of active clients for this period, active = received any service
     * @param Club $club
     * @param int $company_id
     * @param \DateTime $end_date
     * @return Client[]
     */
    private function get402ActiveClientsNumber(Club $club, \DateTime $start, \DateTime $end)
    {
        if ($club->getHomehelptype() == HomeHelp::VISIT) {
            // client numbers for the clubs
            $dql = "SELECT COUNT(DISTINCT c.id) as cls FROM JCSGYKAdminBundle:ClubVisit v JOIN v.client c JOIN c.homehelp h "
                    . "WHERE c.companyId = :company_id AND v.date >= :start AND v.date <= :end";

            if (!empty($club)) {
                $dql .= ' AND h.club = :club';
            }

            $query = $this->em->createQuery($dql)
                ->setParameter('company_id', $this->ds->getCompanyId())
                ->setParameter('start', $start->format('Y-m-d'))
                ->setParameter('end', $end->format('Y-m-d'))
            ;
            if (!empty($club)) {
                $query->setParameter('club', $club);
            }
        } else {
            // client number for club 1 (centrum)
            $dql = "SELECT COUNT(DISTINCT c.client) as cls FROM JCSGYKAdminBundle:HomehelpMonth m JOIN m.hmClients c "
                    . "WHERE m.companyId = :company_id AND m.date >= :start AND m.date <= :end";

            $query = $this->em->createQuery($dql)
                ->setParameter('company_id', $this->ds->getCompanyId())
                ->setParameter('start', $start->format('Y-m-d'))
                ->setParameter('end', $end->format('Y-m-d'))
            ;
        }

        $res = $query->getResult();

        return (!empty($res[0]['cls'])) ? $res[0]['cls'] : 0;
    }

    /**
     * Get the clients for this period
     * @param Club $club
     * @param int $company_id
     * @param \DateTime $end_date
     * @return Client[]
     */
    private function get402Clients(Club $club, \DateTime $start, \DateTime $end)
    {
        $dql = "SELECT c, h FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h "
                . "WHERE c.companyId = :company_id AND h.agreementFrom <= :end AND (h.agreementTo IS NULL OR h.agreementTo >= :start)";

        if (!empty($club)) {
            $dql .= ' AND h.club = :club';
        }

        $query = $this->em->createQuery($dql)
            ->setParameter('company_id', $this->ds->getCompanyId())
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
        ;
        if (!empty($club)) {
            $query->setParameter('club', $club);
        }

        return $query->getResult();
    }

    /**
     * Returns the last Archive record of this client, before the $end date
     * ... or null on failure
     * @param Client $client
     * @param \DateTime $end
     * @return Archive|null
     */
    private function getLastArchiveRecord(Client $client, \DateTime $end)
    {
        $dql = "SELECT x FROM JCSGYKAdminBundle:Archive x WHERE x.client = :client AND x.createdAt < :end ORDER BY x.createdAt DESC";

        $query = $this->em->createQuery($dql)
            ->setParameter('client', $client)
            ->setParameter('end', $end->format('Y-m-d'))
            ->setMaxResults(1)
        ;

        $res = $query->getResult();

        if (!empty($res[0])) {
            return $res[0];
        }

        return null;
    }

    /**
     * Return true if archive record is Archive, false if Reopen
     * @param Archive $archive
     * @return boolean
     */
    private function isArchive(Archive $archive)
    {
        $archive_ids = $this->getParamIds('client_archives');
        $type = $archive->getType();

        if (in_array($type, $archive_ids)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the list of ids of a param group
     * @param string $group
     * @return array
     */
    private function getParamIds($group)
    {
        $params = $this->ds->getGroup($group);
        if (empty($params)) {
            $params = [];
        }

        return array_keys($params);
    }

    private function getArchives(Client $client, \DateTime $start, \DateTime $end)
    {
        $dql = "SELECT x FROM JCSGYKAdminBundle:Archive x WHERE x.client = :client AND x.createdAt >= :start AND x.createdAt < :end ORDER BY x.createdAt";

        $query = $this->em->createQuery($dql)
            ->setParameter('client', $client)
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->setMaxResults(1)
        ;

        return $query->getResult();
    }

    /**
     * Calculate the base stats from this client
     * @param Client $client
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $data
     * @return array
     */
    private function get402BaseData(Client $client, \DateTime $start, \DateTime $end, $data)
    {
        $params = $client->getParams();
        $now = new \DateTime();

        /** @var HomeHelp $homehelp */
        $homehelp = $client->getHomehelp();

        // month start - new/old clients
        if ($homehelp->getAgreementFrom() >= $start) {
            $data['cnum.new']++;
        } else {
            $data['cnum.start']++;
        }
        // month end - closed clients
        if (!$homehelp->hasAgreement($end)) {
            $data['cnum.archived']++;
        } else {
            $data['cnum.end']++;
        }
        // active homehelp
        //if ($homehelp->isActive($end)) {
        //    $data['cnum.active']++;
        //}

        // comfort
        if (!empty($params[200])) {
            if (!isset($data['blocks']['comfort'][$params[200]])) {
                $data['blocks']['comfort'][$params[200]] = 0;
            }
            $data['blocks']['comfort'][$params[200]]++;
        }
        // ownership
        if (!empty($params[204])) {
            if (!isset($data['blocks']['ownership'][$params[204]])) {
                $data['blocks']['ownership'][$params[204]] = 0;
            }
            $data['blocks']['ownership'][$params[204]]++;
        }
        // income
        $k = $this->getRangeKey($homehelp->getIncome(), $this->incomeRanges);
        if (false !== $k) {
            $data['blocks']['income'][$k]++;
        }
        // age
        $gen = $client->getGender();
        $bday = $client->getBirthDate();
        if (!empty($bday)) {
            $age = $now->diff($bday)->format('%y');
            $k = $this->getRangeKey($age, $this->ageRanges);
            if (false !== $k) {
                $data['blocks']['age_' . $gen][$k]++;
            }
        }
        if ($gen == 1) {
            $data['cnum.man']++;
        }
        else {
            $data['cnum.woman']++;
        }

        if ($homehelp->getInpatient()) {
            $data['cnum.inpatient']++;
        }

        return $data;
    }

    private function get402Sums($data)
    {
        // we must deduct the reopened clients from the starting numbers
        $data['cnum.all'] = $data['cnum.start'] + $data['cnum.new'];

        return $data;
    }

    /**
     * Get the stat data from the Home help invoices
     * @param Club $club
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @param array $data
     * @return array
     */
    private function get402InvoiceData(Club $club, \DateTime $start, \DateTime $end, $data)
    {
        $invoices = $this->get402InvoiceResults($club, $start, $end);

        if (!empty($invoices)) {
            foreach ($invoices as $invoice) {
                // loop through the invoices
                $client_id = $invoice->getClient()->getId();
                $items = json_decode($invoice->getItems(), true);

                if (!empty($items)) {
                    // find the cost item
                    $item = [];
                    foreach ($items as $k => $i) {
                        if (is_numeric($k)) {
                            $item = $i;
                            break;
                        }
                    }
                    if (!empty($item)) {
                        $data['inv.hours'] += $item['quantity'];
                        if ($invoice->getAmount() == 0) {
                            $data['inv.disccli'][$client_id] = 1;
                            $data['inv.disc'] += $item['quantity'];
                        } else {
                            $data['inv.paycli'][$client_id] = 1;
                            $data['inv.pay'] += $item['quantity'];
                        }

                        $data['inv.sum'] += $invoice->getAmount();
                        $data['inv.visits'] += $item['visits'];

                        // get average hours
                        $avg = $item['quantity'] / $item['visits'];
                        if ($avg < 0.75) {
                            $data['inv.avg05']++;
                        } elseif($avg <= 2) {
                            $data['inv.avg12']++;
                        } else {
                            $data['inv.avg34']++;
                        }
                    }
                }
            }
        }
        // get only the client counts
        $data['inv.disccli'] = count($data['inv.disccli']);
        $data['inv.paycli'] = count($data['inv.paycli']);

        $data['inv.sum'] = $this->ae->formatCurrency($data['inv.sum']);

        return $data;
    }

    /**
     * Get the invoices for this time period and club
     * @param Club $club
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return Invoice[]
     */
    private function get402InvoiceResults(Club $club, \DateTime $start, \DateTime $end)
    {
        // get the invoices
        $dql = "SELECT i FROM JCSGYKAdminBundle:Invoice i JOIN i.client c JOIN c.homehelp h "
            . "WHERE c.companyId = :company_id AND i.endDate >= :month_start AND i.endDate < :month_end AND i.invoicetype IN (:types) AND h.club = :club AND i.cancelId IS NULL AND i.status != -1 ";

        $query = $this->em->createQuery($dql)
            ->setParameter('company_id', $this->ds->getCompanyId())
            ->setParameter('month_start', $start->format('Y-m-d'))
            ->setParameter('month_end', $end->format('Y-m-d'))
            ->setParameter('types', [Invoice::HOMEHELP])
            ->setParameter('club', $club)
        ;

        return $query->getResult();
    }

    /**
     * Get the stat data from the ClubVisit records
     * @param Club $club
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @param array $data
     * @return array
     */
    private function get402VisitData(Club $club, \DateTime $start, \DateTime $end, $data)
    {
        $visits = $this->get402VisitResults($club, $start, $end);

        if (!empty($visits)) {
            foreach ($visits as $visit) {
                // loop through the visits
                $client_id = $visit->getClient()->getId();
                if ($visit->getVisit()) {
                    $data['inv.visits']++;

                    $events = $visit->getEvents();
                    // not very nice :(
                    foreach ($events as $i => $event) {
                        if ($event) {
                            $data['blocks']['eventcount'][$i]++;
                        }
                    }
                }
            }
        }
        // get only the client counts
        $data['inv.disccli'] = count($data['inv.disccli']);
        $data['inv.paycli'] = count($data['inv.paycli']);

        return $data;
    }

    /**
     * Get the invoices for this time period and club
     * @param Club $club
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return ClubVisit[]
     */
    private function get402VisitResults(Club $club, \DateTime $start, \DateTime $end)
    {
        // get the ClubVisits
        $dql = "SELECT cv FROM JCSGYKAdminBundle:ClubVisit cv JOIN cv.client c JOIN c.homehelp h "
            . "WHERE c.companyId = :company_id AND cv.date >= :month_start AND cv.date < :month_end AND h.club = :club";

        $query = $this->em->createQuery($dql)
            ->setParameter('company_id', $this->ds->getCompanyId())
            ->setParameter('month_start', $start->format('Y-m-d'))
            ->setParameter('month_end', $end->format('Y-m-d'))
            ->setParameter('club', $club)
        ;

        return $query->getResult();
    }


    /**
     * Returns the date when the client was archived
     * @param Client $client
     * @return \DateTime archive date or null on failure
     */
    private function getArchiveDate(Client $client)
    {
        $re = null;
        if ($client->getIsArchived()) {
            // get the archive records
            $archives = $client->getArchives();

            // the first record is the last event
            if (!empty($archives)) {
                $arc = $archives->first();
                $re = $arc->getCreatedAt();
            }
        }

        return $re;
    }


    /**
     * Searches for a value in a range and returns it's key
     * @param $val
     * @param $range
     * @return bool|int|string
     */
    private function getRangeKey($val, $range)
    {
        foreach ($range as $k => $d) {
            if ($val >= $d[0] && $val <= $d[1]) {

                return $k;
            }
        }

        return false;
    }
}