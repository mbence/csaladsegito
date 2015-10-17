<?php

namespace JCSGYK\AdminBundle\Services\Reports;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Catering;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Twig\AdminExtension;
use Doctrine\ORM\EntityManager;

/**
 * catering Report Service
 */
class CateringReport
{
    /** Service container */
    private $container;
    /** @var  DataStore */
    private $ds;
    /** @var AdminExtension Twig formatter */
    private $ae;
    /** @var EntityManager Doctrine Entity Manager */
    private $em;

    /** reports data */
    private $data = [];
    /** OpenTBS template file */
    private $template = 'catering_summary_detailed.xlsx';
    /** Twig template file */
    private $twigTemplate = '_summary_detailed.html.twig';
    /** Output file name */
    private $outputFile;
    private $holidays;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->ds        = $this->container->get('jcs.ds');
        $this->ae        = $this->container->get('jcs.twig.adminextension');
        $this->em        = $this->container->get('doctrine')->getManager();
    }

    public function getForm(&$form_builder, $report)
    {
        $company_id = $this->ds->getCompanyId();

        $form_builder->add('month', 'choice', [
            'label'    => 'Dátum',
            'choices'  => $this->container->get('jcs.invoice')->getMonths($company_id),
            'required' => true,
        ]);
        // clubs
        $form_builder->add('club', 'entity', [
            'label'    => 'Klub',
            'class'    => 'JCSGYKAdminBundle:Club',
            'choices'  => $this->ds->getClubs(),
            'required' => true,
        ]);
    }

    public function run($form_data, $report, $download)
    {
        $this->template = __DIR__ . '/../../Resources/public/reports/' . $this->template;

        $form_data = $this->checkFormData($form_data);
        $month = new \DateTime($form_data['month']);
        $start = $month->format('Y-m-01');
        $end = $month->format('Y-m-t');
        $this->prepareHolidays($start, $end);


        $title = sprintf('%s havi étkezési napok összesítése', $this->ae->formatDate($month, 'ym'));

        $company_id = $this->ds->getCompanyId();

        $report_data = $this->getReport($company_id, $form_data['club'], $start, $end);

        $this->data = [
            'ca.cim'   => $title,
            'ca.klub'  => empty($form_data['club']) ? '' : sprintf(' (%s)', $form_data['club']->getName()),
            'sp.datum' => $this->ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'catering'    => $report_data,
                'months_days' => range(1, 31),
            ]
        ];

        $this->outputFile = $this->data['ca.cim'] . $this->data['ca.klub'] . '.xlsx';

        return $this->output($download);
    }

    /**
     * Read the holidays for this period
     * Codes: 1 - Holiday, 2 - Working weekend, 3 - Rest day
     * @param string $start
     * @param string $end
     */
    private function prepareHolidays($start, $end)
    {
        $this->holidays = $this->ds->getHolidays($start, $end);
    }

    /**
     * Make sure that all settings in the filter are correct
     *
     * @param array $form_data
     * @return array
     * @throws AccessDeniedHttpException
     */
    private function checkFormData($form_data)
    {
        $form_fields = [
            'month' => null,
            'club'  => null,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $sec        = $this->container->get('security.context');
        $company_id = $this->ds->getCompanyId();
        $months     = $this->container->get('jcs.invoice')->getMonths($company_id);

        // non admins not get all clubs
        if (!$sec->isGranted('ROLE_ADMIN') && empty($form_data['club'])) {
            throw new AccessDeniedHttpException();
        }
        // get the period
        if (!isset($months[$form_data['month']])) {
            throw new AccessDeniedHttpException();
        }

        return $form_data;
    }

    /**
     * @param $company_id
     * @param $club
     * @param $start
     * @param $end
     * @return array
     */
    private function getReport($company_id, $club, $start, $end)
    {
        $report = [];
        $empty_month = [];
        foreach (range(1, 31) as $d) {
            $empty_month[$d] = '';
        }
        $summaryRow = [
            'id'       => '',
            'name'     => 'ÖSSZESEN',
            'calendar' => $empty_month,
            'days'     => 0,
            'weekdays' => 0,
        ];
        $orders = $this->em->getRepository('JCSGYKAdminBundle:ClientOrder')->CateringDetailedReport($company_id, $club, $start, $end);

        foreach ($orders as $order) {
            //var_dump($orders);
            $row = [
                'id' => $order['id'],
                'name' => $this->ae->formatName($order['firstname'], $order['lastname'], $order['title']),
                'calendar' => $empty_month,
                'days' => 0,
                'weekdays' => 0
            ];
            $days = explode(',', $order['orders']);

            foreach($days as $day) {
                list($date, $ordered, $cancelled) = explode('|', $day);
                $day_index = (new \DateTime($date))->format('j');
                if ($cancelled) {
                    $row['calendar'][$day_index] = '-';
                } elseif ($ordered) {
                    $row['calendar'][$day_index] = 'X';
                    $row['days']++;
                    $summaryRow['days']++;
                    if (!$this->weekendOrWorkday($date)) {
                        $row['weekdays']++;
                        $summaryRow['weekdays']++;
                    }
                }
            }
            $report[] = $row;
        }

        $report[] = $summaryRow;

        return $report;
    }

    /**
     * Is this date a weekend? Also check the holidays table from the options
     * @param \DateTime $date
     * @return bool true if weekend
     */
    private function weekendOrWorkday($date)
    {
        $date = new \DateTime($date);

        $weekend = ($date->format('N') > 5);
        $isoDate = $date->format('Y-m-d');
        if (isset($this->holidays[$isoDate])) {
            $weekend = in_array($this->holidays[$isoDate], [1, 3]);
        }

        return $weekend;
    }

    /**
     * Generates the output with Twig or OpenTBS
     * @return type
     */
    public function output($download)
    {
        if ($download) {
            return $this->container->get('jcs.docx')->make($this->template, $this->data, $this->outputFile);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $this->twigTemplate, ['data' => $this->data]);
        }
    }
}