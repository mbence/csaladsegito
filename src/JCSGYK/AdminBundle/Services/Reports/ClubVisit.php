<?php

namespace JCSGYK\AdminBundle\Services\Reports;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use JCSGYK\AdminBundle\Entity\Invoice;

/**
 * ClubVisit Report Service
 *
 * Problem paramgroups must be 105 and 111, or else the problem section wont work!
 * We need a better solution for this! :(
 */
class ClubVisit
{

    /** Service container */
    private $container;

    /** Datastore */
    private $ds;

    /** Twig formatter */
    private $ae;

    /** Doctrine Entity Manager */
    private $em;
    private $club;
    private $title;

    /** reports data */
    private $data = [];

    /** OpenTBS template file */
    private $docTemplate;

    /** Twig html template */
    private $twigTemplate;

    /** Output file name */
    private $output;

    /** timespan  */
    private $startDate;
    private $endDate;

    /** Map of the problem parameters */
    private $problemMap = [];

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->ds        = $this->container->get('jcs.ds');
        $this->ae        = $this->container->get('jcs.twig.adminextension');
        $this->em        = $this->container->get('doctrine')->getManager();
    }

    private function getClubName()
    {
        return empty($this->club) ? '' : sprintf(' (%s)', $this->club->getName());
    }

    /**
     * Adds the filter elements to the report form
     * @param string $report
     * @param FormBuilder $form_builder
     */
    public function getForm($report, FormBuilder &$form_builder)
    {
        $company_id = $this->ds->getCompanyId();

        if (in_array($report, ['clubvisit_days'])) {
            $form_builder->add('month', 'choice', [
                'label'    => 'Dátum',
                'choices'  => $this->getClubVisitMonths(),
                'required' => true,
            ]);
            $form_builder->add('club', 'entity', [
                'label'    => 'Klub',
                'class'    => 'JCSGYKAdminBundle:Club',
                'choices'  => $this->ds->getClubs(),
                'required' => true,
            ]);
        }
    }

    private function getClubVisitMonths()
    {
        $dql = "SELECT DISTINCT(DATE_FORMAT(v.date,'%Y-%m')) as date FROM JCSGYKAdminBundle:ClubVisit v WHERE v.companyId = :company_id";
        $res = $this->em->createQuery($dql)
                ->setParameter('company_id', $this->ds->getCompanyId())
                ->setMaxResults(12)
                ->getResult();

        $re = [];
        foreach ($res as $month) {
            $d                  = new \DateTime($month['date']);
            $re[$month['date']] = $this->ae->formatDate($d, 'ym');
        }

        return $re;
    }

    public function run($report, $form_data, $download)
    {
        $this->report = $report;

        $form_data = $this->setDefaults($form_data);
        $this->checkFormData($form_data);
        $this->setParameters($form_data);
        $this->buildData($form_data);

        return $this->output($download);
    }

    /**
     * Make sure we have all required fields
     * @param array $form_data
     * @return array
     */
    private function setDefaults($form_data)
    {
        $form_fields = [];
        if (in_array($this->report, ['clubvisit_days'])) {
            $form_fields = [
                'month' => null,
            ];
        }

        return array_merge($form_fields, $form_data);
    }

    /**
     * Check for user roles and form params
     * @param type $form_data
     * @throws AccessDeniedHttpException
     */
    private function checkFormdata($form_data)
    {
        $company_id = $this->ds->getCompanyId();

        if (in_array($this->report, ['clubvisit_days'])) {
            $sec    = $this->container->get('security.context');
            $months = $this->getClubVisitMonths();

            // non admins must select a club
            if (!$sec->isGranted('ROLE_ADMIN') && empty($form_data['club'])) {
                throw new AccessDeniedHttpException();
            }
            // get the period
            if (!isset($months[$form_data['month']])) {
                throw new AccessDeniedHttpException();
            }
        }
    }

    /**
     * Set club, template names, title and output file name
     * @param array $form_data
     */
    private function setParameters($form_data)
    {
        if (isset($form_data['club'])) {
            $this->club = $form_data['club'];
        }

        if (in_array($this->report, ['clubvisit_days'])) {
            $month              = new \DateTime($form_data['month']);
            $this->title        = sprintf('%s havi látogatási napok összesítése', $this->ae->formatDate($month, 'ym'));
            $this->docTemplate  = __DIR__ . '/../../Resources/public/reports/clubvisit_days.xlsx';
            $this->twigTemplate = 'JCSGYKAdminBundle:Reports:_clubvisit_days.html.twig';
            $this->output       = $output_name        = $this->title . $this->getClubName() . '.xlsx';
        }
    }

    private function buildData($form_data)
    {
        $this->data = [
            'ca.cim'   => $this->title,
            'ca.klub'  => $this->getClubName(),
            'sp.datum' => $this->ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'clubvisit' => $this->getReportData($form_data),
            ]
        ];

        // add calendar days
        if (in_array($this->report, ['clubvisit_days'])) {
            $this->data['blocks']['months_days'] = range(1, 31);
        }
    }

    private function getReportData($form_data)
    {
        $month = new \DateTime($form_data['month']);

        $data = [];
        // find all the invoices and clients of the given month
        $sql  = "SELECT c.id, c.caseLabel, c.title, c.lastname, c.firstname, GROUP_CONCAT(v.date) as dates, GROUP_CONCAT(v.visit) as visits FROM JCSGYKAdminBundle:ClubVisit v JOIN v.client c JOIN c.homehelp h "
                . "WHERE c.companyId = :company_id AND v.date >= :month_start AND v.date <= :month_end AND h.club = :club "
                . "GROUP BY c.id "
                . "ORDER BY c.lastname, c.firstname";

        $res = $this->em->createQuery($sql)
                ->setParameter('company_id', $this->ds->getCompanyId())
                ->setParameter('month_start', $month->format('Y-m-01'))
                ->setParameter('month_end', $month->format('Y-m-t'))
                ->setParameter('club', $this->club)
                ->getResult()
        ;

        if (!empty($res)) {
            // summary
            $sums = [
                'id'      => '',
                'name'    => 'ÖSSZESEN',
                'address' => '',
                'days'    => 0,
            ];

            // empty month
            $empty_month = [];
            foreach (range(1, 31) as $d) {
                $empty_month[$d] = '';
            }
            $sums['calendar'] = $empty_month;

            foreach ($res as $row) {

                $dates    = explode(',', $row['dates']);
                $visits   = explode(',', $row['visits']);

                $data_row = [
                    'id'       => $row['id'],
                    'name'     => $this->ae->formatName($row['firstname'], $row['lastname'], $row['title']),
                    //'address'       => sprintf('(%s)', $ae->formatAddress('', '', $client->getStreet(), $client->getStreetType(), $client->getStreetNumber(), $client->getFlatNumber())),
                    'calendar' => $empty_month,
                    'days'     => array_sum($visits),
                ];

                if (is_array($dates) && is_array($visits) && count($dates) == count($visits)) {
                    foreach ($dates as $i => $date) {
                        $n                        = (new \DateTime($date))->format('j');
                        $data_row['calendar'][$n] = $visits[$i] > 0 ? 'X' : '-';
                    }
                }

                $data[] = $data_row;

                $sums['days'] += $data_row['days'];
            }
            // add the summary to the end of the report
            $data[] = $sums;
        }

        return $data;
    }

    /**
     * Generates the output with Twig or OpenTBS depending on $download
     * @return Response
     */
    public function output($download)
    {
        if ($download) {
            return $this->container->get('jcs.docx')->make($this->docTemplate, $this->data, $this->output);
        }
        else {
            return $this->container->get('templating')->render($this->twigTemplate, ['data' => $this->data]);
        }
    }

}
