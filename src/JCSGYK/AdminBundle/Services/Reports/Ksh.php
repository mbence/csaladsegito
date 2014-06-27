<?php

namespace JCSGYK\AdminBundle\Services\Reports;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Ksh Report Service
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
            'data'     => new \DateTime(date('Y') . '-01-01'),
        ]);
        $form_builder->add('end_date', 'date', [
            'label'    => ' - ',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'type' => 'text'),
            'required' => true,
            'data'     => new \DateTime('today'),
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
        $this->data['sp.datum']   = $this->ae->formatDate(new \DateTime('today'));

        // 2.1 Éves  forgalom - Kapcsolatfelvételek  száma [rep.2-1-01] - number of events
        $this->numberOfEvents();

        // 2.2.  Ellátottak  számára  vonatkozó  adat  (tárgyév)    (Nem  halmozott  adat!)
        $this->newOldClientNumbers();

        // 2.3.  A  szolgáltatást  igénybe  vevők  száma  nem  és  korcsoport  szerint  (fő)
        $this->ageAndSex();


        return $this->output($download);
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
    private function ageAndSex()
    {
        $repository = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Event');

        $qb = $repository->createQueryBuilder('e');
        $qb ->select('DISTINCT(c.id) as id, c.birthDate, c.gender')
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

        // reset data cells
        $prefix = 'rep.2-3-';
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
            }
        }


        //$this->data['rep.2-2-01'] = !empty($res[0][1]) ? $res[0][1] : 0;
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
        if ($download) {
            return $this->container->get('jcs.docx')->make($this->template, $this->data, $this->output);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Elements:dump.html.twig', ['var' => $this->data]);
        }
    }
}