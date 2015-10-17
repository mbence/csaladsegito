<?php

namespace JCSGYK\AdminBundle\Services\Reports;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\Query;

use JCSGYK\AdminBundle\Entity\Client;

/**
 * Ksh Child Welfare Report Service
 *
 * Problem paramgroups must be 105 and 111, or else the problem section wont work!
 * We need a better solution for this! :(
 */
class KshGyk
{
    /** Service container */
    private $container;
    /** Datastore */
    private $ds;
    /** Twig formatter */
    private $ae;
    /** Doctrine Entity Manager */
    private $em;

    /** reports data */
    private $data = [];
    /** OpenTBS template file */
    private $template = 'ksh_gyk.docx';
    /** Output file name */
    private $output = 'KSH_GYK_satisztika_%s.docx';

    /** timespan  */
    private $startDate;
    private $endDate;
    /**
     * End Date obj
     * @var \DateTime
     */
    private $dtEndDate;

    /** Age Ranges */
    private $ageRanges = [
        '01' => [0, 2],
        '02' => [3, 5],
        '03' => [6, 13],
        '04' => [14, 17],
    ];

    /** Map of the 2 endanger parameters */
    private $endangerMap = [];
    private $endangeredClients = [];
    private $caseAdmins = [];

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->ds        = $this->container->get('jcs.ds');
        $this->ae        = $this->container->get('jcs.twig.adminextension');
        $this->em        = $this->container->get('doctrine')->getManager();

        $this->template = __DIR__ . '/../../Resources/public/reports/' . $this->template;
        $this->output   = sprintf($this->output, date('Y-m-d'));
    }

    public function getForm(&$form_builder)
    {
        $form_builder->add('start_date', 'date', [
            'label'    => 'Dátum:',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'type' => 'text'),
            'required' => true,
            'data'     => new \DateTime((date('Y')-1) . '-01-01'),
        ]);
        $form_builder->add('end_date', 'date', [
            'label'    => ' - ',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'type' => 'text'),
            'required' => true,
            'data'     => new \DateTime((date('Y')-1) . '-12-31'),
        ]);
        $form_builder->add('case_admin', 'entity', [
            'label' => 'Szűrés esetgazda szerint (opcionális)',
            'class' => 'JCSGYKAdminBundle:User',
            'choices' => $this->ds->getCaseAdmins(),
            'required' => false,
            'multiple' => true,
            'expanded' => true,
        ]);
    }

    public function run($form_data, $download)
    {
        // timespan
        $this->startDate = null;
        $this->endDate = null;
        $this->data['sp.start_date'] = ' ';
        $this->data['sp.end_date'] = ' ';
        $this->data['rep.title'] = 'A GYERMEKJÓLÉTI ALAPELLÁTÁSOK MŰKÖDÉSI ADATAI 2014';

        if (!empty($form_data['start_date'])) {
            $this->startDate = $form_data['start_date']->format('Y-m-d');
            $this->data['sp.start_date'] = $this->ae->formatDate($form_data['start_date']);
        }
        if (!empty($form_data['end_date'])) {
            $this->dtEndDate = $form_data['end_date'];
            $this->endDate = $form_data['end_date']->format('Y-m-d');
            $this->data['sp.end_date'] = $this->ae->formatDate($form_data['end_date']);
        }
        if (!empty($form_data['case_admin'])) {
            $this->caseAdmins = $form_data['case_admin'];
        }
        $this->data['sp.datum']   = $this->ae->formatDate(new \DateTime('today'));

        $this->prepareDataArrays();

        // 1.2. A gyermekjóléti szolgálat gondozási tevékenysége
        $this->clientNumbers();
        // 1.5. A jelzőrendszer által küldött jelzések száma
        $this->signals();

        return $this->output($download);
    }

    private function prepareDataArrays()
    {
        $this->data['blocks']['rep.1-2-heders'] = $this->ds->getGroup(101);
        $this->data['blocks']['rep.1-2-heders']['missing'] = 'Hiányos adatlap';
        $this->data['blocks']['rep.1-2-heders']['sum'] = 'Összesen';
        // produce the empty array with the above keys
        $empty_1_2 = array_fill_keys(array_keys($this->data['blocks']['rep.1-2-heders']), 0);

        $col_count = count($this->data['blocks']['rep.1-2-heders']) + 1;
        for ($i = 1; $i <= 9; $i++) {
            $this->data['blocks']['rep.1-2-0' . $i] = $empty_1_2;
        }
        // row for missing age
        $this->data['blocks']['rep.1-2-xx'] = $empty_1_2;
        // family distinct arrays
        foreach ($this->data['blocks']['rep.1-2-09'] as &$cell) {
            $cell = [];
        }

        // 1.5 list
        $signals = $this->container->get('jcs.ds')->getGroup('signals');
        $this->data['blocks']['rep.1-5'] = $this->prepareBlock($signals);

        // 1.6
        $problems = $this->container->get('jcs.ds')->getGroup(105);
        $this->data['blocks']['rep.1-6'] = $this->prepareBlock($problems);

        // 1.7. A gyermekjóléti szolgálat szakmai tevékenységeinek adatai
        $events = $this->container->get('jcs.ds')->getGroup(106);
        $this->data['blocks']['rep.1-7'] = $this->prepareBlock($events);

        // 1.8. A prevenciós szolgáltatások adatai
        $prevents = $this->container->get('jcs.ds')->getGroup(107);
        $this->data['blocks']['rep.1-8'] = $this->prepareBlock($prevents);

        // 1.9. A speciális szolgáltatások adatai
        $specs = $this->container->get('jcs.ds')->getGroup(112);
        $this->data['blocks']['rep.1-9'] = $this->prepareBlock($specs);

        // 1.13.  Veszélyeztetett gyermekek adatai
        $endanger = $this->container->get('jcs.ds')->getGroup(113);
        $this->data['blocks']['rep.1-13'] = $this->prepareBlock($endanger);
        $this->prepareEndangerBlock();
    }

    private function prepareBlock($params)
    {
        $re = [];
        $n = 1;
        foreach ($params as $pid => $param) {
            $re[$pid] = [
                'n'       => str_pad($n, 2, '0', STR_PAD_LEFT),
                'label'   => $param,
                'count'   => 0,
                'clients' => 0,
            ];
            $n++;
        }
        $re['sum'] = [
            'n'       => str_pad($n, 2, '0', STR_PAD_LEFT),
            'label'   => 'Összesen',
            'count'   => 0,
            'clients' => 0,
        ];

        return $re;
    }

    /**
     * Data structure is not suitable for the stats, we must hack it and hope that all goes well
     */
    private function prepareEndangerBlock()
    {
        // Create a map for 113 and 114
        $endanger = $this->container->get('jcs.ds')->getGroup(113);
        $multidanger = array_flip($this->container->get('jcs.ds')->getGroup(114));
        $this->endangerMap = [];
        foreach ($endanger as $k => $dng) {
            if (isset($multidanger[$dng])) {
                $this->endangerMap[$multidanger[$dng]] = $k;
            }
        }
    }

    private function clientNumbers()
    {
        $clients = $this->queryClients();
        foreach ($clients as $res) {
            //var_dump($res);die;
            $client = $res[0];
            $params = json_decode($client['parameters'], true);
            $age = $client['birthDate'];
            $rows = $this->get12Rows($client);

            // we need the 101 key here
            if (empty($params['101']) || !isset($this->data['blocks']['rep.1-2-01'][$params['101']])) {
                foreach ($rows as $row) {
                    $this->data['blocks']['rep.1-2-' . $row]['missing']++;
                }
                $this->data['blocks']['rep.1-2-09']['missing'][$client['caseLabel']] = 1;
            } else {
                foreach ($rows as $row) {
                    $this->data['blocks']['rep.1-2-' . $row][$params['101']]++;
                }
                // families
                $this->data['blocks']['rep.1-2-09'][$params['101']][$client['caseLabel']] = 1;
            }
            // summary col
            foreach ($rows as $row) {
                $this->data['blocks']['rep.1-2-' . $row]['sum']++;
            }
            // families
            $this->data['blocks']['rep.1-2-09']['sum'][$client['caseLabel']] = 1;

            // 1.6 problems
            $this->countParams($res['pp'], 'rep.1-6', 105);
            // 1.7 events
            $this->countParams($res['ep'], 'rep.1-7', 106);
            // 1.8 prevents
            $this->countParams($res['ep'], 'rep.1-8', 107);
            // 1.9 specs
            $this->countParams($res['ep'], 'rep.1-9', 112);
            // 1.13 endanger
            $this->countEndangers($client);
        }

        // sum the families
        foreach ($this->data['blocks']['rep.1-2-09'] as &$cell) {
            $cell = array_sum($cell);
        }

        $this->groupEndangerRows();
    }

    private function queryClients()
    {
        $clients_repo = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Client');
        $qb = $clients_repo->createQueryBuilder('c');
        $qb ->select("c, GROUP_CONCAT(p.parameters) as pp, GROUP_CONCAT(e.parameters) as ep")
            ->leftJoin('c.problems', 'p')
            ->leftJoin('p.events', 'e')
            ->where('c.companyId = :company_id')->setParameter('company_id', $this->ds->getCompanyId())
            ->andWhere('c.type = :client_type')->setParameter('client_type', Client::CW)
        ;
        if (!empty($this->startDate)){
            $qb->andWhere('e.eventDate >= :start_date')->setParameter('start_date', $this->startDate);
        }
        if (!empty($this->endDate)){
            $qb->andWhere('e.eventDate <= :end_date')->setParameter('end_date', $this->endDate);
        }
        if (!empty($this->caseAdmins) && $this->caseAdmins->count()) {
            $qb->andWhere('c.caseAdmin IN (:case_admin)')->setParameter('case_admin', $this->caseAdmins);
        }
        $qb->groupBy('c.id');
        // we use array hydration for read-only queries to use less memory and to remove proxy queries to catering and homehelp, that are unrelevant here

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    private function countParams($in, $data_key, $param_group)
    {
        $params = $this->getParamTypes($in, $param_group);
        foreach ($params as $pid => $p_count) {
            $this->data['blocks'][$data_key][$pid]['count'] += $p_count;
            $this->data['blocks'][$data_key][$pid]['clients'] ++;
            $this->data['blocks'][$data_key]['sum']['count'] += $p_count;
            $this->data['blocks'][$data_key]['sum']['clients'] ++;
        }
    }

    private function getParamTypes($in, $pid)
    {
        $re = [];
        // change the group concat separator
        $in = str_replace('},{', '};{', $in);
        $rows = explode(';', $in);
        foreach ($rows as $p) {
            $dec = json_decode($p, true);
            if (!empty($dec[$pid])) {
                if (empty($re[$dec[$pid]])) {
                    $re[$dec[$pid]] = 0;
                }
                $re[$dec[$pid]]++;
            }
        }

        return $re;
    }

    private function get12Rows($client)
    {
        $rows = ['05']; // summary
        if ($client['gender'] == 2) {
            $rows[] = '06';
        }
        // age is compared to the end of statistics period
        if (empty($client['birthDate'])) {
            $rows[] = 'xx';
        } else {
            $age = $client['birthDate']->diff($this->dtEndDate)->y;
            $age_row = $this->getRangeKey($age, $this->ageRanges);
            // fallback for missing range
            if (false === $age_row) {
                $age_row = 'xx';
            }
            $rows[] = $age_row;
        }

        return $rows;
    }

    private function signals()
    {
        $signals = $this->querySignals();
        foreach ($signals as $signal) {
            $this->data['blocks']['rep.1-5'][$signal['dispatch']]['count'] = $signal['co'];
            $this->data['blocks']['rep.1-5']['sum']['count'] += $signal['co'];
        }
    }

    private function querySignals()
    {
        $task_repo = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Task');
        $qb        = $task_repo->createQueryBuilder('t');
        $qb->select('t.dispatch, count(t) as co')
                ->leftJoin('t.client', 'c')
                ->where('c.companyId = :company_id')->setParameter('company_id', $this->ds->getCompanyId())
                ->andWhere('c.type = :client_type')->setParameter('client_type', Client::CW)
                ->andWhere('t.dispatch IS NOT NULL')
        ;
        if (!empty($this->startDate)) {
            $qb->andWhere('t.createdAt >= :start_date')->setParameter('start_date', $this->startDate);
        }
        if (!empty($this->endDate)) {
            $qb->andWhere('t.createdAt <= :end_date')->setParameter('end_date', $this->endDate);
        }
        if (!empty($this->caseAdmins) && $this->caseAdmins->count()) {
            $qb->andWhere('c.caseAdmin IN (:case_admin)')->setParameter('case_admin', $this->caseAdmins);
        }
        $qb->groupBy('t.dispatch');
        // we use array hydration for read-only queries to use less memory and to remove proxy queries to catering and homehelp, that are unrelevant here

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    private function getRangeKey($val, $range)
    {
        foreach ($range as $k => $d) {
            if ($val >= $d[0] && $val <= $d[1]) {

                return $k;
            }
        }

        return false;
    }

    private function countEndangers($client)
    {
        $params = json_decode($client['parameters'], true);
        if (!empty($params[113])) {
            $this->data['blocks']['rep.1-13'][$params[113]]['count'] ++;
            // client count
            if (130 != $params[113]) {
                $this->endangeredClients[$client['id']] = 1;
            }
        }

        if (!empty($params[114])) {
            $this->data['blocks']['rep.1-13'][$this->endangerMap[$params[114]]]['clients'] ++;
        }


    }

    private function groupEndangerRows()
    {
        // remove the non dangered
        unset($this->data['blocks']['rep.1-13'][130]);
        unset($this->data['blocks']['rep.1-13']['sum']);
        $ungrpd = $this->data['blocks']['rep.1-13'];

        $n = 1;
        // add client numbers
        $this->data['blocks']['rep.1-13'] =[[
            'n' => str_pad($n, 2, '0', STR_PAD_LEFT),
            'label' => 'Nyilvántartott veszélyezetetett kiskorúak száma',
            'count' => array_sum($this->endangeredClients),
            'clients' => '',
            'sub' => false,
            'colspan' => 2,
        ]];

        $groups = [
            ['n' => 15, 'label' => 'Környezeti főcsoport összesen (a gyermek környezetéből kell kiindulni)'],
            ['n' => 7, 'label' => 'Magatartási főcsoport összesen (az érintett gyermekre vonatkozóan)'],
            ['n' => 3, 'label' => 'Egészségi főcsoport összesen (az érintett gyermekre vonatkozóan)'],
        ];

        foreach ($groups as $k => $g) {
            $n++;
            // add group summary
            $this->data['blocks']['rep.1-13']['sum' . $k] = [
                'n' => str_pad($n, 2, '0', STR_PAD_LEFT),
                'label' => $g['label'],
                'count' => 0,
                'clients' => 0,
                'sub' => false,
                'colspan' => 2,
            ];
            $sub = true;
            for ($i = 0; $i < $g['n']; $i++) {
                $n++;
                $row = array_shift($ungrpd);
                // subheaders for the first row (rowspan)
                $row['sub'] = $sub ? $g['n'] : false;
                if ($row['sub']) {
                    $row['sublabel'] = str_pad(($n-1), 2, '0', STR_PAD_LEFT);
                    $sub = false;
                }
                $row['n'] = str_pad($n, 2, '0', STR_PAD_LEFT);

                $this->data['blocks']['rep.1-13']['sum' . $k]['count'] += $row['count'];
                $this->data['blocks']['rep.1-13']['sum' . $k]['clients'] += $row['clients'];
                $this->data['blocks']['rep.1-13'][] = $row;
            }
        }
    }

    /**
     * Generates the output with OpenTBS
     * @return type
     */
    public function output($download)
    {
        if ($download) {
            return $this->container->get('jcs.docx')->make($this->template, $this->data, $this->output);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:_ksh_gyk.html.twig', ['data' => $this->data]);
        }
    }
}