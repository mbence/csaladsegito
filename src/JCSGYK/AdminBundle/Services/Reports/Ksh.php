<?php

namespace JCSGYK\AdminBundle\Services\Reports;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Ksh Report Service
 *
 * Problem paramgroups must be 105 and 111, or else the problem section wont work!
 * We need a better solution for this! :(
 */
class Ksh
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
    private $template = 'ksh.docx';
    /** Output file name */
    private $output = 'KSH_satisztika_%s.docx';

    /** timespan  */
    private $startDate;
    private $endDate;

    /** Age Ranges */
    private $ageRanges = [
        'a' => [ 0,  6],
        'b' => [ 7, 13],
        'c' => [14, 17],
        'd' => [18, 34],
        'e' => [35, 49],
        'f' => [50, 61],
        'g' => [62, 999],
    ];

    /** Map of the problem parameters */
    private $problemMap = [];

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
        $this->makeProblemMap();
    }

    public function getForm(&$form_builder)
    {
        $form_builder->add('start_date', 'date', [
            'label'    => 'Dátum:',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'type' => 'text'),
            'required' => true,
            'data'     => new \DateTime(date('Y') . '-01-01'),
        ]);
        $form_builder->add('end_date', 'date', [
            'label'    => ' - ',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'type' => 'text'),
            'required' => true,
            'data'     => new \DateTime('today'),
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

        if (!empty($form_data['start_date'])) {
            $this->startDate = $form_data['start_date']->format('Y-m-d');
            $this->data['sp.start_date'] = $this->ae->formatDate($form_data['start_date']);
        }
        if (!empty($form_data['end_date'])) {
            $this->endDate = $form_data['end_date']->format('Y-m-d');
            $this->data['sp.end_date'] = $this->ae->formatDate($form_data['end_date']);
        }
        if (!empty($form_data['case_admin'])) {
            $this->caseAdmins = $form_data['case_admin'];
        }
        $this->data['sp.datum']   = $this->ae->formatDate(new \DateTime('today'));

        // 2.1 Éves  forgalom - Kapcsolatfelvételek  száma [rep.2-1-01] - number of events
        $this->numberOfEvents();

        // 2.2.  Ellátottak  számára  vonatkozó  adat  (tárgyév)    (Nem  halmozott  adat!)
        $this->newOldClientNumbers();

        // 2.3.  A  szolgáltatást  igénybe  vevők  száma  nem  és  korcsoport  szerint  (fő)
        $this->ageSexParams();

        // problems
        $this->problems();

        // events
        $this->events();

        return $this->output($download);
    }

    /**
     * There are 2 different param groups that represent the same problem types.
     * We need to merge them, hence this map is created.
     */
    private function makeProblemMap()
    {
        // get the groups
        $g105 = $this->ds->getGroup(105);
        $g111 = $this->ds->getGroup(111);

        // find the matches
        foreach ($g111 as $k => $v) {
            $pos = array_search($v, $g105);
            if (false !== $pos) {
                $this->problemMap[$k] = $pos;
            }
        }
    }

    /**
     * Get the Number of events on the given timespan
     */
    private function numberOfEvents()
    {
        $repository = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Event');

        $qb = $repository->createQueryBuilder('e');
        $qb ->select('COUNT(e)')
            ->leftJoin('e.problem', 'p')
            ->leftJoin('p.client', 'c')
            ->where('c.companyId = :company_id')->setParameter('company_id', $this->ds->getCompanyId())

            ->andWhere('e.isDeleted=0')
            ->andWhere('e.clientCancel=0')
        ;
        if (!empty($this->startDate)){
            $qb->andWhere('e.eventDate >= :start_date')->setParameter('start_date', $this->startDate);
        }
        if (!empty($this->endDate)){
            $qb->andWhere('e.eventDate <= :end_date')->setParameter('end_date', $this->endDate);
        }
        if (!empty($this->caseAdmins) && $this->caseAdmins->count()){
            $qb->andWhere('c.caseAdmin IN (:case_admin)')->setParameter('case_admin', $this->caseAdmins);
        }

        $res = $qb->getQuery()->getResult();

        $this->data['rep.2-1-01'] = !empty($res[0][1]) ? $res[0][1] : 0;
    }

    /**
     * Get the new and old clients numbers in this period
     */
    private function newOldClientNumbers()
    {
        $repository = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Event');

        $qb = $repository->createQueryBuilder('e');
        $qb ->select('COUNT(DISTINCT c)')
            ->leftJoin('e.problem', 'p')
            ->leftJoin('p.client', 'c')
            ->where('c.companyId = :company_id')->setParameter('company_id', $this->ds->getCompanyId())

            ->andWhere('e.isDeleted=0')
            ->andWhere('e.clientCancel=0')
        ;
        if (!empty($this->startDate)){
            $qb->andWhere('e.eventDate >= :start_date')->setParameter('start_date', $this->startDate);
        }
        if (!empty($this->endDate)){
            $qb->andWhere('e.eventDate <= :end_date')->setParameter('end_date', $this->endDate);
        }
        $qb2 = clone $qb;
        if (!empty($this->startDate)){
            $qb->andWhere('c.createdAt < :start_date')->setParameter('start_date', $this->startDate);
            $qb2->andWhere('c.createdAt >= :start_date')->setParameter('start_date', $this->startDate);
        }
        if (!empty($this->caseAdmins) && $this->caseAdmins->count()){
            $qb->andWhere('c.caseAdmin IN (:case_admin)')->setParameter('case_admin', $this->caseAdmins);
        }

        $res = $qb->getQuery()->getResult();
        $res2 = $qb2->getQuery()->getResult();

        // old clients
        $this->data['rep.2-2-01'] = !empty($res[0][1]) ? $res[0][1] : 0;
        // new clients
        $this->data['rep.2-2-02'] = !empty($res2[0][1]) ? $res2[0][1] : 0;
        // all
        $this->data['rep.2-2-03'] = (string) ($this->data['rep.2-2-01'] + $this->data['rep.2-2-02']);
    }

    /**
     * Get the clients numbers grouped by age and sex
     */
    private function ageSexParams()
    {
        $repository = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Client');
        $qb = $repository->createQueryBuilder('c');
        $qb ->select("c.id as id, c.birthDate, c.gender, COUNT(e.id) AS event_count, c.parameters, "
                //. "GROUP_CONCAT(p.parameters) AS problem_params, GROUP_CONCAT(e.eventDate) AS event_dates, "
                . "MIN(e.eventDate) AS min_ed, MAX(e.eventDate) AS max_ed")
            ->leftJoin('c.problems', 'p')
            ->leftJoin('p.events', 'e')
            ->where('c.companyId = :company_id')->setParameter('company_id', $this->ds->getCompanyId())

            ->andWhere('e.isDeleted=0')
            ->andWhere('e.clientCancel=0')
        ;
        $qb->groupBy('c.id');
        if (!empty($this->startDate)){
            $qb->andHaving('max_ed >= :start_date')->setParameter('start_date', $this->startDate);
        }
        if (!empty($this->endDate)){
            $qb->andHaving('min_ed <= :end_date')->setParameter('end_date', $this->endDate);
        }
        if (!empty($this->caseAdmins) && $this->caseAdmins->count()){
            $qb->andWhere('c.caseAdmin IN (:case_admin)')->setParameter('case_admin', $this->caseAdmins);
        }

        // reset data cells
        $prefix = 'rep.2-3-';
        $prefix2 = 'rep.3-1-';

        $this->resetPrefix([$prefix, $prefix2]);

        foreach ($this->ageRanges as $k => $v) {
            $this->data[$prefix . '01' . $k] = 0;
            $this->data[$prefix . '02' . $k] = 0;
            $this->data[$prefix . '03' . $k] = 0;
        }
        $this->data[$prefix . '01h'] = 0;
        $this->data[$prefix . '02h'] = 0;
        $this->data[$prefix . '03h'] = 0;

        $now = new \DateTime();
        $res = $qb->getQuery()->getResult();

        // reset parameter counts
        $params = $this->resetParamGroupFields([101, 102, 103]);

        foreach ($res as $row) {
            // sex
            $gen = $row['gender'] == 1 ? '01' : '02';

            // we must skip those without a birthdate
            if ($row['birthDate'] instanceof \DateTime) {
                // age intervals

                $age = $now->diff($row['birthDate'])->format('%y');
                $col = $this->getRangeKey($age, $this->ageRanges);
                // not in range ?!
                if (false === $col) {
                    continue;
                }

                $key = $prefix . $gen . $col;
                $this->data[$key] ++;
                // sum row
                $this->data[$prefix . '03' . $col] ++;
                // sum col
                $this->data[$prefix . $gen . 'h'] ++;
                // allsum
                $this->data[$prefix . '03h'] ++;

                // 3.1 Azon  esetek  vonatkozásában,  ahol  a  szakmai  tevékenység  egy  találkozás  kapcsán   tett  intézkedéssel  nem  zárható  le
                // more then 1 event for the client
                if ($row['event_count'] > 1) {
                    $key2 = $prefix2 . $gen . $col;
                    $this->data[$key2] ++;
                    // sum row
                    $this->data[$prefix2 . '03' . $col] ++;
                    // sum col
                    $this->data[$prefix2 . $gen . 'h'] ++;
                    // allsum
                    $this->data[$prefix2 . '03h'] ++;
                }
            }

            // parameters
            $this->countParams($row['parameters'], $params);
        }
    }

    /**
     * Resets the counters for the given prefix keys
     * @param array $prefixes
     */
    private function resetPrefix(array $prefixes)
    {
        foreach ($prefixes as $prefix) {
            foreach ($this->ageRanges as $k => $v) {
                $this->data[$prefix . '01' . $k] = 0;
                $this->data[$prefix . '02' . $k] = 0;
                $this->data[$prefix . '03' . $k] = 0;
            }
            $this->data[$prefix . '01h'] = 0;
            $this->data[$prefix . '02h'] = 0;
            $this->data[$prefix . '03h'] = 0;
        }
    }

    private function problems()
    {
        $repository = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Problem');

        $qb = $repository->createQueryBuilder('p');
        $qb ->select('p.id as id, p.parameters')
            ->leftJoin('p.events', 'e')
            ->leftJoin('p.client', 'c')
            ->where('c.companyId = :company_id')->setParameter('company_id', $this->ds->getCompanyId())

            ->andWhere('e.isDeleted=0')
            ->andWhere('p.isDeleted=0')
            ->andWhere('e.clientCancel=0')
        ;
        if (!empty($this->startDate)){
            $qb->andWhere('e.eventDate >= :start_date')->setParameter('start_date', $this->startDate);
        }
        if (!empty($this->endDate)){
            $qb->andWhere('e.eventDate <= :end_date')->setParameter('end_date', $this->endDate);
        }
        if (!empty($this->caseAdmins) && $this->caseAdmins->count()){
            $qb->andWhere('c.caseAdmin IN (:case_admin)')->setParameter('case_admin', $this->caseAdmins);
        }
        $qb->groupBy('p.id');
        $res = $qb->getQuery()->getResult();

        // reset
        $params = $this->resetParamGroupFields([105, 111]);
        $this->data['rep.3-5-13'] = 0;
        $this->data['rep.3-5-14'] = 0;

        foreach ($res as $row) {
            // parameters
            $this->countParams($row['parameters'], $params);
        }
    }

    /**
     * Resets the fields in the blocks for the given param groups
     * @param array $param_groups
     * @return type
     */
    private function resetParamGroupFields(array $param_groups)
    {
        $params = [];
        foreach ($param_groups as $group_id) {
            $params[$group_id] = $this->ds->getGroup($group_id);
            $this->data['blocks']['param' . $group_id] = [];

            $n = 1;
            foreach ($params[$group_id] as $key => $label) {
                $this->data['blocks']['param' . $group_id][$key] = [
                    'id'    => str_pad($n, 2, "0", STR_PAD_LEFT),
                    'label' => $label,
                    'count' => 0
                ];
                $n++;
            }
            $this->data['p' . $group_id . '.num'] = str_pad($n, 2, "0", STR_PAD_LEFT);
            $this->data['p' . $group_id . '.sum'] = 0;

        }

        return $params;
    }

    private function countParams($param_cell, $params)
    {
        $client_params = json_decode($param_cell, true);
        foreach ($params as $group_id => $param_lists) {
            if (isset($client_params[$group_id])) {
                if (is_array($client_params[$group_id])) {
                    // multi select fields
                    foreach ($client_params[$group_id] as $param_id) {
                        if (!empty($param_id) && isset($params[$group_id][$param_id])) {
                            // check if we need to remap the param_id?
                            $group_id2 = $group_id;
                            if ($group_id == 111 && isset($this->problemMap[$param_id])) {
                                $group_id2 = 105;
                                $param_id = $this->problemMap[$param_id];
                            }
                            $this->data['blocks']['param' . $group_id2][$param_id]['count'] ++;
                            $this->data['p' . $group_id2 . '.sum'] ++;
                            if ($param_id == 46) {
                                $this->data['rep.3-5-14'] ++;
                            }
                        }
                    }
                }
                else {
                    // single fields
                    $param_id = $client_params[$group_id];
                    if (!empty($param_id) && isset($params[$group_id][$param_id])) {
                        // check if we need to remap the param_id?
                        $group_id2 = $group_id;
                        if ($group_id == 111 && isset($this->problemMap[$param_id])) {
                            $group_id2 = 105;
                            $param_id = $this->problemMap[$param_id];
                        }
                        $this->data['blocks']['param' . $group_id2][$param_id]['count'] ++;
                        $this->data['p' . $group_id2 . '.sum'] ++;
                        if ($param_id == 46) {
                            $this->data['rep.3-5-14'] ++;
                        }
                    }
                }
                // multi problems
                if ($group_id == 111 && count($client_params[$group_id]) > 0) {
                    $this->data['rep.3-5-13'] ++;
                }
            }
        }
    }

    private function events()
    {
        $repository = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Client');
        // get event parameters 106, 108
        $params = $this->resetParamGroupFields([106, 108]);
        foreach ($params as $group_id => $parameters) {
            // get the clients number who have this params
            foreach ($parameters as $pid => $pname) {

                $qb = $repository->createQueryBuilder('c');
                $qb->select('COUNT(DISTINCT c.id) AS client_count')
                    ->leftJoin('c.problems', 'p')
                    ->leftJoin('p.events', 'e')
                    ->where('c.companyId = :company_id')->setParameter('company_id', $this->ds->getCompanyId())
                    ->andWhere('e.isDeleted=0')
                    ->andWhere('p.isDeleted=0')
                    ->andWhere('e.clientCancel=0')
                    ->andWhere('e.parameters LIKE :param_id')->setParameter('param_id', '%:' . $pid . '%')
                ;
                if (!empty($this->startDate)) {
                    $qb->andWhere('e.eventDate >= :start_date')->setParameter('start_date', $this->startDate);
                }
                if (!empty($this->endDate)) {
                    $qb->andWhere('e.eventDate <= :end_date')->setParameter('end_date', $this->endDate);
                }
                if (!empty($this->caseAdmins) && $this->caseAdmins->count()){
                    $qb->andWhere('c.caseAdmin IN (:case_admin)')->setParameter('case_admin', $this->caseAdmins);
                }

                $res = $qb->getQuery()->getResult();
                if (!empty($res[0]['client_count'])) {
                    $this->data['blocks']['param' . $group_id][$pid]['count'] = $res[0]['client_count'];
                    $this->data['p' . $group_id . '.sum'] += $res[0]['client_count'];
                }
            }
        }
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

    /**
     * Generates the output with OpenTBS
     * @return type
     */
    public function output($download)
    {
        // temp fix
        $download = true;

        if ($download) {
            return $this->container->get('jcs.docx')->make($this->template, $this->data, $this->output);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Elements:dump.html.twig', ['var' => $this->data]);
        }
    }
}