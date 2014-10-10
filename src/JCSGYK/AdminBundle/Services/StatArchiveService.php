<?php

namespace JCSGYK\AdminBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

use JCSGYK\AdminBundle\Entity\StatArchive;
use JCSGYK\AdminBundle\Entity\StatFile;
use JCSGYK\AdminBundle\Entity\Club;
use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\HomeHelp;

/**
 * Statistics Service
 */
class StatArchiveService
{
    /** Service container */
    private $container;

    /**
     * @var JCSGYK\AdminBundle\Services\DataStore;
     */
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
        // check for user roles and set the params accordingly
        $company_id = $this->ds->getCompanyId();
        /** @var InvoiceService $invoice_service */
        $invoice_service = $this->container->get('jcs.invoice');

        $month = $start;
        $start_date = $start->format('Y-m-d');
        $end_date   = $end->format('Y-m-d');
        $now = new \DateTime();

        $data = [
            'ca.cim'          => sprintf('%s havi EBÉD statisztika', $this->ae->formatDate($month, 'ym')),
            'ca.klub'         => empty($club) ? '' : sprintf(' (%s)', $club->getName()),
            'sp.datum'        => $this->ae->formatDate(new \DateTime('today')),
            'cnum.start'      => 0,
            'cnum.new'        => 0,
            'cnum.all'        => 0,
            'cnum.active'     => [],
            'cnum.archived'   => 0,
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
        $data['blocks']['income_headers'] = [
            0 => '28 500 alatt',
            1 => '57 000-ig',
            2 => '85 000-ig',
            3 => '114 000-ig',
            4 => '114 000 felett'
        ];
        $income_ranges = [
            0 => [0, 28500],
            1 => [28501, 57000],
            2 => [57001, 85000],
            3 => [85001, 114000],
            4 => [114001, 99999999],
        ];
        foreach ($income_ranges as $k => $v) {
            $data['blocks']['income'][$k] = 0;
        }
        $data['blocks']['age_headers'] = [
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
        $age_ranges = [
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
        foreach ($age_ranges as $k => $v) {
            $data['blocks']['age_2'][$k] = 0;
            $data['blocks']['age_1'][$k] = 0;
        }

        // get the invoices
        $sql0 = "SELECT c, a, i FROM JCSGYKAdminBundle:Invoice i LEFT JOIN i.client c LEFT JOIN c.catering a "
                . "WHERE c.companyId = :company_id AND i.startDate >= :month_start AND i.endDate <= :month_end AND i.invoicetype IN (:types)";
        if (!empty($club)) {
            $sql0 .= ' AND a.club = :club';
        }

        $q0 = $this->em->createQuery($sql0)
            ->setParameter('company_id', $company_id)
            ->setParameter('month_start', $start_date)
            ->setParameter('month_end', $end_date)
            ->setParameter('types', [Invoice::MONTHLY, Invoice::DAILY])
        ;
        if (!empty($club)) {
            $q0->setParameter('club', $club);
        }
        $res0 = $q0->getResult();

        // save the invoice data for later
        $invoices = [];
        foreach ($res0 as $invoice) {
            $client_id = $invoice->getClient()->getId();
            if (empty($invoices[$client_id])) {
                $invoices[$client_id] = $invoice;
            }
        }

        // get all the clients
        $sql = "SELECT o, c, a FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c LEFT JOIN c.catering a "
                . "WHERE c.companyId = :company_id AND o.date >= :month_start AND o.date <= :month_end ";
        if (!empty($club)) {
            $sql .= ' AND a.club = :club';
        }
        $sql .= ' GROUP BY c.id';

        $q = $this->em->createQuery($sql)
            ->setParameter('company_id', $company_id)
            ->setParameter('month_start', $start_date)
            ->setParameter('month_end', $end_date);
        if (!empty($club)) {
            $q->setParameter('club', $club);
        }
        $res = $q->getResult();

        foreach ($res as $order) {
            // loop through the clients
            $client = $order->getClient();

            $params = $client->getParams();
            if ($client->getIsArchived() == 0) {
                if ($client->getCreatedAt() < $month) {
                    $data['cnum.start']++;
                }
                else {
                    $data['cnum.new']++;
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
                $k = $this->getRangeKey($client->getCatering()->getIncome(), $income_ranges);
                if (false !== $k) {
                    $data['blocks']['income'][$k]++;
                }
                // age
                $gen = $client->getGender();
                $bday = $client->getBirthDate();
                if (!empty($bday)) {
                    $age = $now->diff($bday)->format('%y');
                    $k = $this->getRangeKey($age, $age_ranges);
                    if (false !== $k) {
                        $data['blocks']['age_' . $gen][$k]++;
                    }
                }
                if ($gen == 1) {
                    $data['cnum.man']++;
                }
                elseif ($gen == 2) {
                    $data['cnum.woman']++;
                }
            }
            else {
                $data['cnum.archived']++;
            }
        }

        // get all client orders for this period
        $sql2 = "SELECT o, c, a FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c LEFT JOIN c.catering a "
                . "WHERE c.companyId = :company_id AND o.date >= :month_start AND o.date <= :month_end ";
        if (!empty($club)) {
            $sql2 .= ' AND a.club = :club';
        }

        $q2 = $this->em->createQuery($sql2)
            ->setParameter('company_id', $company_id)
            ->setParameter('month_start', $start_date)
            ->setParameter('month_end', $end_date);
        if (!empty($club)) {
            $q2->setParameter('club', $club);
        }
        $res2 = $q2->getResult();

        // temp array for client caterinf cost
        $client_costs = [];

        foreach ($res2 as $order) {
            // loop through the orders
            $client_id = $order->getClient()->getId();
            if (empty($client_costs[$client_id])) {
                $cost = null;
                // new client, get the catering cost
                if (!empty($invoices[$client_id])) {
                    $items = json_decode($invoices[$client_id]->getItems(), true);
                    $costs = array_keys($items);
                    arsort($costs);
                    $temp_cost = reset($costs);
                    if (is_numeric($temp_cost) && $temp_cost >= 0) {
                        $client_costs[$client_id] = $temp_cost;
                    }
                    $cost = $temp_cost;
                }

                // no invoice found for this client, calculate the catering cost from the costs table and client income
                if (is_null($cost)) {
                    $table = $this->ds->getOption('cateringcosts', $order->getDate()->format('Y-m-d'));
                    $cost = $invoice_service->getCostForADay($order->getClient()->getCatering(), $table);
                }
            }
            else {
                $cost = $client_costs[$client_id];
            }

            if ($order->getOrder() && !$order->getCancel()) {
                $data['inv.days'] ++;
                $data['cnum.active'][$client_id] = 1;

                $weekend = ($order->getDate()->format('N') > 5);

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
        }

        // get only the client counts
        $data['inv.discweekcli'] = count($data['inv.discweekcli']);
        $data['inv.discendcli'] = count($data['inv.discendcli']);
        $data['inv.payweekcli'] = count($data['inv.payweekcli']);
        $data['inv.payendcli'] = count($data['inv.payendcli']);
        // is active?
        $data['cnum.active'] = count($data['cnum.active']);

        $data['cnum.all'] = $data['cnum.start'] + $data['cnum.new'];
        $data['cnum.end'] = $data['cnum.all'] - $data['cnum.archived'];

        $data['inv.sum'] = $this->ae->formatCurrency($data['inv.sum']);

        $template_file = __DIR__ . '/../Resources/public/reports/catering_stats.xlsx';

        return [
            'type' => !empty($club) ? $club->getId() : null,
            'data' => $data,
            'file' => $this->container->get('jcs.docx')->make($template_file, $data, null)
        ];
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
        // check for user roles and set the params accordingly
        $company_id = $this->ds->getCompanyId();
        /** @var InvoiceService $invoice_service */
        $invoice_service = $this->container->get('jcs.invoice');

        $month = $start;
        $start_date = $start->format('Y-m-d');
        $end_date   = $end->format('Y-m-d');
        $now = new \DateTime();

        $data = [
            'ca.cim'          => sprintf('%s havi GONDOZÁS statisztika', $this->ae->formatDate($month, 'ym')),
            'ca.klub'         => empty($club) ? '' : sprintf(' (%s)', $club->getName()),
            'sp.datum'        => $this->ae->formatDate(new \DateTime('today')),
            'cnum.start'      => 0,
            'cnum.new'        => 0,
            'cnum.all'        => 0,
            'cnum.active'     => 0,
            'cnum.archived'   => 0,
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
        $data['blocks']['income_headers'] = [
            0 => '28 500 alatt',
            1 => '57 000-ig',
            2 => '85 000-ig',
            3 => '114 000-ig',
            4 => '114 000 felett'
        ];
        $income_ranges = [
            0 => [0, 28500],
            1 => [28501, 57000],
            2 => [57001, 85000],
            3 => [85001, 114000],
            4 => [114001, 99999999],
        ];
        foreach ($income_ranges as $k => $v) {
            $data['blocks']['income'][$k] = 0;
        }
        $data['blocks']['age_headers'] = [
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
        $age_ranges = [
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
        foreach ($age_ranges as $k => $v) {
            $data['blocks']['age_2'][$k] = 0;
            $data['blocks']['age_1'][$k] = 0;
        }

        // get all the clients for this period
        $sql = "SELECT c, h FROM JCSGYKAdminBundle:Client c JOIN c.homehelp h "
                . "WHERE c.companyId = :company_id AND c.createdAt <= :end";
        if (!empty($club)) {
            $sql .= ' AND h.club = :club';
        }

        $q = $this->em->createQuery($sql)
            ->setParameter('company_id', $company_id)
            ->setParameter('end', $end_date)
        ;
        if (!empty($club)) {
            $q->setParameter('club', $club);
        }
        $res = $q->getResult();

        // loop through the clients
        foreach ($res as $client) {

            $params = $client->getParams();
            // check if the client was active or archived in this period
            $is_archived = false;
            if ($client->getIsArchived()) {
                // she is archived now, but when was she archived?
                $archive_date = $this->getArchiveDate($client);
                if (!is_null($archive_date)) {
                    if ($start <= $archive_date && $end >= $archive_date) {
                        // client was archived during the stat period
                        $data['cnum.archived']++;
                        // we don't need her data, unless she was added in this month
                        if ($client->getCreatedAt() < $month) {
                            $is_archived = true;
                        }
                    }
                    elseif ($month > $archive_date) {
                        // client was archived before the start
                        $is_archived = true;
                    }
                    // otherwise the client was archived after the end of the period, that means we still need her data
                }
            }

            if (!$is_archived) {
                /** @var HomeHelp $homehelp */
                $homehelp = $client->getHomehelp();

                if ($client->getCreatedAt() < $month) {
                    $data['cnum.start']++;
                }
                else {
                    $data['cnum.new']++;
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
                $k = $this->getRangeKey($homehelp->getIncome(), $income_ranges);
                if (false !== $k) {
                    $data['blocks']['income'][$k]++;
                }
                // age
                $gen = $client->getGender();
                $bday = $client->getBirthDate();
                if (!empty($bday)) {
                    $age = $now->diff($bday)->format('%y');
                    $k = $this->getRangeKey($age, $age_ranges);
                    if (false !== $k) {
                        $data['blocks']['age_' . $gen][$k]++;
                    }
                }
                if ($gen == 1) {
                    $data['cnum.man']++;
                }
                elseif ($gen == 2) {
                    $data['cnum.woman']++;
                }
                // active homehelp
                if ($client->getHomehelp()->getIsActive()) {
                    $data['cnum.active']++;
                }
                if ($homehelp->getInpatient()) {
                    $data['cnum.inpatient']++;
                }
            }
        }

        // get the invoices
        $sql0 = "SELECT i FROM JCSGYKAdminBundle:Invoice i JOIN i.client c JOIN c.homehelp h "
            . "WHERE c.companyId = :company_id AND i.endDate >= :month_start AND i.endDate <= :month_end AND i.invoicetype IN (:types)";
        if (!empty($club)) {
            $sql0 .= ' AND h.club = :club';
        }

        $q0 = $this->em->createQuery($sql0)
            ->setParameter('company_id', $company_id)
            ->setParameter('month_start', $start_date)
            ->setParameter('month_end', $end_date)
            ->setParameter('types', [Invoice::HOMEHELP])
        ;
        if (!empty($club)) {
            $q0->setParameter('club', $club);
        }
        $res0 = $q0->getResult();
        if (!empty($res0)) {
            foreach ($res0 as $invoice) {
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

        $data['cnum.all'] = $data['cnum.start'] + $data['cnum.new'];
        $data['cnum.end'] = $data['cnum.all'] - $data['cnum.archived'];

        $data['inv.sum'] = $this->ae->formatCurrency($data['inv.sum']);

        $template_file = __DIR__ . '/../Resources/public/reports/homehelp_stats.xlsx';

        return [
            'type' => !empty($club) ? $club->getId() : null,
            'data' => $data,
            'file' => $this->container->get('jcs.docx')->make($template_file, $data, null)
        ];
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