<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

use JCSGYK\AdminBundle\Entity\Stat;
use JCSGYK\AdminBundle\Entity\UserRepository;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Entity\StatFile;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\HomeHelp;
use JCSGYK\AdminBundle\Entity\Relation;

class ReportsController extends Controller
{
    private $reports = [];

    /** @var DataStore */
    private $ds;

    /**
    * @Secure(roles="ROLE_USER")
    */
    public function indexAction($report = null)
    {
        $this->ds = $this->container->get('jcs.ds');

        // check if user has access to the selected report
        $this->checkRole($report);

        $result  = null;
        $request = $this->getRequest();

        // get the form for the selected report
        $form = $this->getReportForm($report);

        // render and download reports
        if ($request->isMethod('POST')) {
            $form->bind($request);
            $download = $request->request->get('download', false);

            $result = $this->getReport($report, $form->getData(), $download);

            if ($download) {
                return $result;
            }
        }

        return $this->render('JCSGYKAdminBundle:Reports:index.html.twig', [
            'menu'   => $this->getMenu(),
            'report' => $report,
            'result' => $result,
            'form'   => $form ? $form->createView() : null,
        ]);
    }

    /**
     * Checks the user role for the selected report
     *
     * @param string $report
     * @return boolean true on success
     * @throws AccessDeniedHttpException on failure
     */
    private function checkRole($report)
    {
        // no report was selected
        if (is_null($report)) {
            return true;
        }

        $granted = false;

        $menu = $this->getMenu();
        foreach ($menu as $m2) {
            foreach ($m2 as $item) {
                if ($item['slug'] == $report) {
                    $granted = true;
                    break(2);
                }
            }
        }

        if (!$granted) {
            throw new AccessDeniedHttpException();
        }

        return true;
    }

    /**
     * Produces the various forms for the reports
     *
     * @param string $report
     * @return Symfony\Component\Form\Form
     */
    private function getReportForm($report)
    {
        if (is_null($report)) {
            return false;
        }

        $sec              = $this->container->get('security.context');
        $client_type_list = $this->ds->getClientTypes();

        /** @var Formbuilder $form_builder */
        $form_builder = $this->createFormBuilder();
        //$form_builder->setData([]);

        if (in_array($report, ['clients', 'catering_clients', 'homehelp_clients', 'clubvisit_clients'])) {
            $this->container->get('jcs.reports.clients')->getForm($form_builder, $report);
        }
        if (in_array($report, ['catering_summary_detailed'])) {
            $this->container->get('jcs.reports.catering')->getForm($form_builder, $report);
        }
        elseif ('casecounts' == $report) {
            // client types (if relevant)
            if (count($client_type_list) > 1) {
                $this->getClientTypeSelect($form_builder, $client_type_list);
            }
            // admins can select the users of this company
            if ($sec->isGranted('ROLE_ADMIN')) {
                $this->getCaseAdminSelect($form_builder, false, 'mind');
            }
        }
        elseif ('catering_orders' == $report) {
            // weeks
            $this->getCateringWeeks($form_builder);
        }
        elseif (in_array($report, ['catering_cashbook', 'homehelp_cashbook'])) {
            // day
            $this->getDayInput($form_builder, false, 'Nap');
            // months
            $this->getInvoiceMonths($form_builder, false, 'Hónap');

            if ('homehelp_cashbook' == $report) {
                $form_builder->add('club', 'hidden', [
                    'data' => '1'
                ]);
            }
        }
        elseif (in_array($report, ['catering_summary', 'catering_datacheck'])) {
            // months
            $this->getInvoiceMonths($form_builder);
        }
        elseif (in_array($report, ['homehelp_summary', 'homehelp_visits', 'homehelp_hours', 'clubvisit_days'])) {
            // months
            $this->getHomeHelpInvoiceMonths($form_builder);
        }
        elseif (in_array($report, ['catering_stats', 'homehelp_stats', 'clubvisit_stats'])) {
            // stat months
            $this->getStatMonths($form_builder, $report);

            if ('homehelp_stats' == $report) {
                $form_builder->add('club', 'hidden', [
                    'data' => '1'
                ]);
            }
        }
        elseif ('ksh' == $report) {
            $this->container->get('jcs.reports.ksh')->getForm($form_builder);
        }
        elseif ('ksh_gyk' == $report) {
            $this->container->get('jcs.reports.ksh_gyk')->getForm($form_builder);
        }

        // club select for all catering reports
        if (in_array($report, ['catering_orders', 'catering_cashbook', 'catering_summary', 'catering_datacheck', 'catering_stats', 'clubvisit_stats'])) {
            $this->getClubSelect($form_builder);
        }
        elseif (in_array($report, ['homehelp_clients_old'])) {
            $this->getSocialWorkerSelect($form_builder, true, 'Mindenki');
        }

        // show only debts for catering summary
        if (in_array($report, ['catering_summary', 'catering_datacheck'])) {
            $form_builder->add('only_debts', 'checkbox', [
                'label'       => 'Csak tartozások',
                'required'    => false,
                'label_attr'  => ['style' => 'float:right;margin:2px 0 0 2px;'],
            ]);
        }

        // ClubVisit Days
        if (in_array($report, ['clubvisit_days'])) {
            $this->container->get('jcs.reports.clubvisit')->getForm($report, $form_builder);
        }

        return $form_builder->getForm();
    }

    private function getClientTypeSelect(&$form_builder, $client_type_list)
    {
        $form_builder->add('client_type', 'choice', [
            'label'       => 'Ügyfél típus',
            'choices'     => $client_type_list,
            'required'    => false,
            'empty_value' => 'összes',
        ]);
    }

    private function getCaseAdminSelect(&$form_builder, $required = true, $empty_value = '')
    {
        $form_builder->add('case_admin', 'entity', [
            'label'       => 'Esetgazda',
            'class'       => 'JCSGYKAdminBundle:User',
            'choices'     => $this->ds->getCaseAdmins(null, false),
            'required'    => $required,
            'empty_value' => $empty_value,
        ]);
    }

    // list the months when we have invoice data
    private function getInvoiceMonths(&$form_builder, $required = true, $label = 'Dátum')
    {
        $company_id = $this->ds->getCompanyId();

        $form_builder->add('month', 'choice', [
            'label'    => $label,
            'choices'  => $this->container->get('jcs.invoice')->getMonths($company_id),
            'required' => $required,
        ]);
    }

    // list the months when we have invoice data
    private function getHomeHelpInvoiceMonths(&$form_builder, $required = true, $label = 'Dátum')
    {
        $company_id = $this->ds->getCompanyId();

        $form_builder->add('month', 'choice', [
            'label'    => $label,
            'choices'  => $this->container->get('jcs.invoice')->getMonths($company_id, Invoice::HOMEHELP),
            'required' => $required,
        ]);
    }

    /**
     * Return the list of months when we have statistics data
     * @param FormBuilder $form_builder
     * @param $report
     * @param bool|type $required
     * @param type|string $label
     */
    private function getStatMonths(FormBuilder &$form_builder, $report, $required = true, $label = 'Dátum')
    {
        $company_id = $this->ds->getCompanyId();
        $em         = $this->container->get('doctrine')->getManager();
        $ae         = $this->container->get('jcs.twig.adminextension');

        $stat_type = 'catering_stats' == $report ? 401 : 402;

        $archs  = $em->getRepository('JCSGYKAdminBundle:StatArchive')->findBy(['companyId' => $company_id, 'type' => $stat_type], ['createdAt' => 'DESC'], 12);
        $months = [];
        foreach ($archs as $sa) {
            $months[$sa->getId()] = $ae->formatDate($sa->getStart(), 'ym');
        }

        $form_builder->add('stat_archive', 'choice', [
            'label'    => $label,
            'choices'  => $months,
            'required' => $required,
        ]);
    }

    private function getCateringWeeks(&$form_builder)
    {
        $form_builder->add('week', 'choice', [
            'label'    => 'Hét',
            'choices'  => $this->getWeeks(),
            'data'     => (new \DateTime('this week'))->format('Y-m-d'),
            'required' => true,
        ]);
    }

    private function getDayInput(&$form_builder, $required = true, $label = 'Dátum')
    {
        $form_builder->add('day', 'date', [
            'label'    => $label,
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'type' => 'text'),
            'required' => $required,
            'data'     => new \DateTime('today'),
        ]);
    }

    private function getWeeks()
    {
        $re       = [];
        $ae       = $this->container->get('jcs.twig.adminextension');
        $date     = new \DateTime('last week 0:0'); // 0h 0m 0s the first day of last week
        $one_week = new \DateInterval('P1W');

        for ($i = 0; $i < 6; $i++) {
            $end_of_week            = clone $date;
            $end_of_week->modify('+ 6 days');
            $re[$date->format('Y-m-d')] = sprintf('%s (%s - %s)', $ae->formatDate($date, 'week'), $ae->formatDate($date, 'md'), $ae->formatDate($end_of_week, 'md'));
            $date->add($one_week);
        }

        return $re;
    }

    private function getClubSelect(&$form_builder, $required = true, $empty_value = '')
    {
        $c = [
            'label'    => 'Klub',
            'class'    => 'JCSGYKAdminBundle:Club',
            'choices'  => $this->ds->getClubs(),
            'required' => $required,
        ];
        if (!empty($empty_value)) {
            $c['empty_value'] = $empty_value;
        }

        $form_builder->add('club', 'entity', $c);
    }

    private function getSocialWorkerSelect(&$form_builder, $required = true, $empty_value = '')
    {
        $c = [
            'label'    => 'Gondozó',
            'choices'  => $this->ds->getSocialWorkers(),
            'required' => $required,
        ];
        if (!empty($empty_value)) {
            $c['empty_value'] = $empty_value;
        }

        $form_builder->add('social_worker', 'choice', $c);
    }

    private function getReport($report, $form_data, $download)
    {
        if (in_array($report, ['clients', 'catering_clients', 'homehelp_clients', 'clubvisit_clients'])) {
            return $this->container->get('jcs.reports.clients')->run($form_data, $report, $download);
        }
        if (in_array($report, ['catering_summary_detailed'])) {
            return $this->container->get('jcs.reports.catering')->run($form_data, $report, $download);
        }
        elseif ('casecounts' == $report) {
            return $this->getCasecountsReport($form_data, $download);
        }
        elseif ('catering_orders' == $report) {
            return $this->getCateringOrderReport($form_data, $download);
        }
        elseif (in_array($report, ['catering_summary', 'catering_datacheck'])) {
            return $this->getCateringReport($form_data, $report, $download);
        }
        elseif (in_array($report, ['catering_stats', 'homehelp_stats', 'clubvisit_stats'])) {
            return $this->getStats($form_data, $download);
        }
        elseif (in_array($report, ['catering_cashbook', 'homehelp_cashbook'])) {
            return $this->getCateringCashBookReport($form_data, $download, $report);
        }
        elseif ('ksh' == $report) {
            return $this->container->get('jcs.reports.ksh')->run($form_data, $download);
        }
        elseif ('ksh_gyk' == $report) {
            return $this->container->get('jcs.reports.ksh_gyk')->run($form_data, $download);
        }
        elseif ('homehelp_clients_old' == $report) {
            return $this->getHomehelpClientsReport($form_data, $download);
        }
        elseif (in_array($report, ['homehelp_summary', 'homehelp_visits', 'homehelp_hours'])) {
            return $this->getHomehelpReport($form_data, $report, $download);
        }
        elseif (in_array($report, ['clubvisit_days'])) {
            return $this->container->get('jcs.reports.clubvisit')->run($report, $form_data, $download);
        }

        return false;
    }

    private function getMenu()
    {
        $sec = $this->container->get('security.context');
        $ds  = $this->container->get('jcs.ds');

        // role checks
        $family_help_on    = $ds->companyHas(Client::FH) && $sec->isGranted('ROLE_FAMILY_HELP');
        $child_welfare_on = $ds->companyHas(Client::CW) && $sec->isGranted('ROLE_CHILD_WELFARE');
        $catering_on      = $ds->companyHas(Client::CA) && $sec->isGranted('ROLE_CATERING');

        $menu = [];
        if ($family_help_on) {
            $menu['Családsegítő'] = [
                ['slug' => 'clients', 'label' => 'Ügyfelek'],
                ['slug' => 'casecounts', 'label' => 'Esetszámok'],
                ['slug' => 'ksh', 'label' => 'KSH statisztika'],
            ];
        }
        if ($child_welfare_on) {
            $menu['Gyermekjólét'] = [
                ['slug' => 'clients', 'label' => 'Ügyfelek'],
                ['slug' => 'casecounts', 'label' => 'Esetszámok'],
                ['slug' => 'ksh_gyk', 'label' => 'KSH statisztika'],
            ];
        }
        if ($catering_on) {
            $menu['Étkeztetés'] = [
                ['slug' => 'catering_clients', 'label' => 'Ügyfelek'],
                ['slug' => 'catering_orders', 'label' => 'Heti ebédrendelések'],
                ['slug' => 'catering_cashbook', 'label' => 'Pénztárkönyv'],
                ['slug' => 'catering_summary', 'label' => 'Havi ebédösszesítő'],
                ['slug' => 'catering_summary_detailed', 'label' => 'Részletes ebédösszesítő'],
                ['slug' => 'catering_datacheck', 'label' => 'Adategyeztető'],
                ['slug' => 'catering_stats', 'label' => 'Étkeztetés statisztika'],
            ];
            $menu['Gondozás (Központ)'] = [
                ['slug' => 'homehelp_clients', 'label' => 'Gondozási ügyfelek'],
                ['slug' => 'homehelp_stats', 'label' => 'Gondozás statisztika'],
                ['slug' => 'homehelp_visits', 'label' => 'Gondozási napok'],
                ['slug' => 'homehelp_hours', 'label' => 'Gondozási órák'],
                ['slug' => 'homehelp_clients_old', 'label' => 'Gondozottak'],
                ['slug' => 'homehelp_summary', 'label' => 'Havi gondozás összesítő'],
                ['slug' => 'homehelp_cashbook', 'label' => 'Pénztárkönyv'],
            ];
            $menu['Látogatás (Klubok)'] = [
                ['slug' => 'clubvisit_clients', 'label' => 'Látogatási ügyfelek'],
                ['slug' => 'clubvisit_stats', 'label' => 'Látogatás statisztika'],
                ['slug' => 'clubvisit_days', 'label' => 'Látogatási napok'],
            ];
        }

        return $menu;
    }

    private function getCasecountsReport($form_data)
    {
        $form_fields = [
            'case_admin'  => null,
            'client_type' => null,
        ];
        // make sure we have all required fields
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $em               = $this->getDoctrine();
        $sec              = $this->container->get('security.context');
        $ae               = $this->container->get('jcs.twig.adminextension');
        $client_type_list = $this->ds->getClientTypes();
        $client_type_keys = array_keys($client_type_list);
        $company_id       = $this->ds->getCompanyId();

        // non admins can only see their own clients
        if (!$sec->isGranted('ROLE_ADMIN')) {
            $form_data['case_admin'] = $sec->getToken()->getUser()->getId();
        }
        // make sure the client type is correct
        if (count($client_type_keys) < 2 && !in_array($form_data['client_type'], $client_type_keys)) {
            $form_data['client_type'] = reset($client_type_keys);
        }

        $counts = $em->getRepository('JCSGYKAdminBundle:Client')->getCaseCounts($company_id, $form_data['case_admin'], $form_data['client_type']);

        $data  = [
            'blocks' => [
                'casecount' => $counts,
            ]
        ];
        $template_file = __DIR__ . '/../Resources/public/reports/casecounts.xlsx';
        $output_name   = 'esetszam_kimutatas_' . date('Ymd') . '.xlsx';
        $send          = $this->container->get('jcs.docx')->makeReport($template_file, $data, $output_name);

        return $send;
    }

    private function getCateringOrderReport($form_data, $download)
    {
        $form_fields = [
            'week' => null,
            'club' => null,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $em         = $this->container->get('doctrine')->getManager();
        $sec        = $this->container->get('security.context');
        $ae         = $this->container->get('jcs.twig.adminextension');
        $company_id = $this->ds->getCompanyId();
        $weeks      = $this->getWeeks();

        // non admins not get all clubs
        if (!$sec->isGranted('ROLE_ADMIN') && empty($form_data['club'])) {
            throw new AccessDeniedHttpException();
        }
        // get the period
        if (!isset($weeks[$form_data['week']])) {
            throw new AccessDeniedHttpException();
        }

        $start_date = new \DateTime($form_data['week']);
        $end_date   = clone $start_date;
        $end_date   = $end_date->modify('+ 6 days');

        $menus = $this->ds->getGroup('lunch_types');

        $summary_data = [];
        // get only the first character from the menu names
        foreach ($menus as $m_id => $menu) {
            $new_name     = '';
            $name_parts   = explode(' ', $menu);
            $new_name    .= end($name_parts)[0];
            $menus[$m_id] = $new_name;

            // add the menu to the summary
            $summary_data[$m_id] = [
                'menu'  => $menu,
                1       => 0,
                2       => 0,
                3       => 0,
                4       => 0,
                5       => 0,
                6       => 0,
                7       => 0,
                'total' => 0,
            ];
        }
        // add totals
        $summary_data[] = [
            'menu'  => '',
            1       => '',
            2       => '',
            3       => '',
            4       => '',
            5       => '',
            6       => '',
            7       => '',
            'total' => '',
        ];
        $summary_data['sum1'] = [
            'menu'  => 'Összesen',
            1       => 0,
            2       => 0,
            3       => 0,
            4       => 0,
            5       => 0,
            6       => 0,
            7       => 0,
            'total' => '',
        ];
        $summary_data['sum2'] = [
            'menu'  => '',
            1       => '',
            2       => '',
            3       => '',
            4       => '',
            5       => 0,
            6       => '',
            7       => 0,
            'total' => 0,
        ];

        $sql = "SELECT o, c.id, c.title, c.lastname, c.firstname, c.socialSecurityNumber, c.zipCode, c.city, c.street, c.streetType, c.streetNumber, c.flatNumber"
                . " FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c LEFT JOIN c.catering a"
                . " WHERE o.companyId = :company_id AND o.date >= :start_date AND o.date <= :end_date";
        if (!empty($form_data['club'])) {
            $sql .= ' AND a.club = :club';
        }
        $sql .= " ORDER BY c.lastname, c.firstname, o.date";

        $q = $em->createQuery($sql)
                ->setParameter('company_id', $company_id)
                ->setParameter('start_date', $start_date->format('Y-m-d'))
                ->setParameter('end_date', $end_date->format('Y-m-d'));
        if (!empty($form_data['club'])) {
            $q->setParameter('club', $form_data['club']);
        }
        $res = $q->getResult();


        $report_data = [];
        foreach ($res as $rec) {
            // we skip the suspended days
            if ($rec[0]->getOrder()) {
                // create the user row
                if (empty($report_data[$rec['id']])) {
                    $addr = $ae->formatAddress('', '', '', $rec['street'], $rec['streetType'], $rec['streetNumber'], $rec['flatNumber']);
                    $ssn = $ae->formatSSN($rec['socialSecurityNumber'], ' ');
                    $report_data[$rec['id']] = [
                        'ssn'     => !empty($ssn) ? $ssn : '   -   ',
                        'name'    => $ae->formatName($rec['firstname'], $rec['lastname'], $rec['title']),
                        'address' => !empty($addr) ? $addr : ' ',
                        1         => '',
                        2         => '',
                        3         => '',
                        4         => '',
                        5         => '',
                        6         => '',
                        7         => '',
                    ];
                }
                $day_num = $rec[0]->getDate()->format('N');
                $o       = '';

                if ($rec[0]->getCancel()) {
                    $o = '-';
                }
                else {
                    $o = isset($menus[$rec[0]->getMenu()]) ? $menus[$rec[0]->getMenu()] : '?';
                    $summary_data[$rec[0]->getMenu()][$day_num] ++;
                    $summary_data[$rec[0]->getMenu()]['total'] ++;
                    $summary_data['sum1'][$day_num] ++;
                    if ($day_num < 6) {
                        $summary_data['sum2'][5] ++;
                    }
                    else {
                        $summary_data['sum2'][7] ++;
                    }
                    $summary_data['sum2']['total'] ++;
                }

                $report_data[$rec['id']][$day_num] = $o;
            }
        }

//        var_dump($report_data);
//        die;

        $data = [
            'ca.cim'           => sprintf('%s. %s. heti ebéd rendelések', $start_date->format('Y'), $start_date->format('W')),
            'ca.klub'          => empty($form_data['club']) ? '' : sprintf(' (%s)', $form_data['club']->getName()),
            'sp.datum'         => $ae->formatDate(new \DateTime('today')),
            'total.sum'        => $summary_data['sum2']['total'],
            'total.unit_price' => $ae->formatCurrency($this->ds->getMenuCost()),
            'total.amount'     => $ae->formatCurrency($this->ds->getMenuCost() * $summary_data['sum2']['total']),
            'blocks'           => [
                'catering' => $report_data,
                'summary'  => $summary_data,
            ]
        ];

        $template_file = __DIR__ . '/../Resources/public/reports/catering_orders.xlsx';
        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';

        if ($download) {

            return $this->container->get('jcs.docx')->make($template_file, $data, $output_name);
        }
        else {

            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:_orders.html.twig', ['data' => $data]);
        }
    }

    private function getCateringReport($form_data, $report, $download)
    {
        $form_fields = [
            'month' => null,
            'club'  => null,
            'only_debts' => false,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $sec        = $this->container->get('security.context');
        $ae         = $this->container->get('jcs.twig.adminextension');
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

        $month = new \DateTime($form_data['month']);

        $report_data = $this->container->get('jcs.invoice')->getCateringReport($company_id, $month, $form_data['club'], $report, $form_data['only_debts']);

        if ('catering_datacheck' == $report) {
            $title = sprintf('%s havi adategyeztető', $ae->formatDate($month, 'ym'));
            $template_file = __DIR__ . '/../Resources/public/reports/catering_datacheck.xlsx';
            $twig_tpl = '_datacheck.html.twig';
        }
        else {
            $title = sprintf('%s havi ebéd összesítő', $ae->formatDate($month, 'ym'));
            $template_file = __DIR__ . '/../Resources/public/reports/catering_summary.xlsx';
            $twig_tpl = '_summary.html.twig';
        }

        $data = [
            'ca.cim'   => $title,
            'ca.klub'  => empty($form_data['club']) ? '' : sprintf(' (%s)', $form_data['club']->getName()),
            'sp.datum' => $ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'catering' => $report_data,
            ]
        ];

        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';

        if ($download) {

            return $this->container->get('jcs.docx')->make($template_file, $data, $output_name);
        }
        else {

            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $twig_tpl, ['data' => $data]);
        }
    }

    private function getCateringCashBookReport($form_data, $download, $report)
    {
        $form_fields = [
            'day'   => null,
            'month' => null,
            'club'  => null,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $sec        = $this->container->get('security.context');
        $ae         = $this->container->get('jcs.twig.adminextension');
        $company_id = $this->ds->getCompanyId();
        $em         = $this->container->get('doctrine')->getManager();

        // non admins not get all clubs
        if (!$sec->isGranted('ROLE_ADMIN') && empty($form_data['club'])) {
            throw new AccessDeniedHttpException();
        }
        // get the period
        if (empty($form_data['day']) && empty($form_data['month'])) {
            throw new AccessDeniedHttpException();
        }

        $date = !empty($form_data['day']) ? $form_data['day']->format('Y-m-d') : $form_data['month'];

        // get the payments for this date
        $invoice_types = 'homehelp_cashbook' == $report ? [Invoice::HOMEHELP] : [Invoice::MONTHLY, Invoice::DAILY];

        $sql = "SELECT i, c, a, h FROM JCSGYKAdminBundle:Invoice i LEFT JOIN i.client c LEFT JOIN c.catering a LEFT JOIN c.homehelp h "
                . "WHERE i.companyId = :company_id AND i.payments LIKE :date AND i.invoicetype IN (:types) ";

        if (!empty($form_data['club'])) {
            $join_table = 'homehelp_cashbook' == $report ? 'h' : 'a';
            $sql .= " AND {$join_table}.club = :club ";
        }
        $sql .=  " ORDER BY c.lastname, c.firstname ";

        $q = $em->createQuery($sql)
            ->setParameter('company_id', $company_id)
            ->setParameter('date', "%$date%")
            ->setParameter('types', $invoice_types)
        ;
        if (!empty($form_data['club'])) {
            $q->setParameter('club', $form_data['club']);
        }
        $res = $q->getResult();

        $sums        = [
            'id'             => '',
            'name'           => 'ÖSSZESEN',
            'address'        => '',
            'month'        => '',
            'invoice_number' => '',
            'amount'         => 0,
        ];
        $report_data = [];
        foreach ($res as $invoice) {
            $client = $invoice->getClient();

            // process items
            $amount = 0;
            $payment_dates = [];

            foreach ($invoice->getPayments() as $payment) {
                // either the payment is on the exact day that we are looking for
                if ($date == $payment[0] ||
                        // or on that month, if only the month is given
                        (empty($form_data['day']) && $date == substr($payment[0], 0, 7))) {

                    $amount += $payment[1];

                    if (empty($payment_dates)) {
                        $payment_dates[] = substr($payment[0], 5);
                    }
                }
            }

            $data_row = [
                'id'             => $client->getCaseLabel(),
                'name'           => $ae->formatClientName($client),
                'address'        => sprintf('(%s)', $ae->formatClientAddress($client)),
                'month'          => !empty($form_data['day']) ? $this->ds->getMonth($invoice->getStartdate()->format('n')) : implode(', ', $payment_dates),
                'invoice_number' => 'SZ-' . $invoice->getId(),
                //'balance'       => $ae->formatCurrency2($catering->getBalance()),
                'amount'         => $ae->formatCurrency2($amount),
            ];

            $report_data[] = $data_row;

            $sums['amount']    += $amount;
        }

        // format the summary
        $sums['amount'] = $ae->formatCurrency2($sums['amount']);
        // add the summary to the end of the report
        $report_data[] = $sums;

        $title_date = !empty($form_data['day']) ? $ae->formatDate($form_data['day']) : $ae->formatDate(new \DateTime($form_data['month']), 'ym');
        $title_type = 'catering_cashbook' == $report ? 'EBÉD' : 'GONDOZÁS';
        $title = sprintf('%s Pénztárkönyv %s', $title_type, $title_date);
        $template_file = __DIR__ . '/../Resources/public/reports/catering_cashbook.xlsx';
        $twig_tpl = '_catering_cashbook.html.twig';

        if (empty($form_data['club']) || $form_data['club'] instanceof Club) {
            $club = $form_data['club'];
        } else {
            $club = $em->getRepository('JCSGYKAdminBundle:Club')->find($form_data['club']);
        }

        $data = [
            'ca.cim'   => $title,
            'ca.klub'  => empty($club) ? '' : sprintf(' (%s)', $club->getName()),
            'sp.datum' => $ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'catering' => $report_data,
            ]
        ];

        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';

        if ($download) {
            return $this->container->get('jcs.docx')->make($template_file, $data, $output_name);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $twig_tpl, ['data' => $data]);
        }
    }

    /**
     * Return the stats by either rendering a twig template, or sending a db stored file
     * @param $form_data
     * @param $download
     * @return Response
     */
    private function getStats($form_data, $download)
    {
        $form_fields = [
            'stat_archive' => null,
            'club'  => null,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $em         = $this->container->get('doctrine')->getManager();
        $sec        = $this->container->get('security.context');

        // non admins should not get all clubs
        if (!$sec->isGranted('ROLE_ADMIN') && empty($form_data['club'])) {
            throw new AccessDeniedHttpException();
        }
        // get the club record
        /** @var StatFile $sf */
        $sf = $em->getRepository('JCSGYKAdminBundle:StatFile')->findOneBy(['statArchive' => $form_data['stat_archive'], 'type' => $form_data['club']]);

        if (empty($sf)) {
            throw new BadRequestHttpException('Invalid Club Id');
        }

        $stat_type = $sf->getStatArchive()->getType();

        $data = $sf->getData();

        if (401 == $stat_type) {
            // catering
            $twig_tpl = '_catering_stats.html.twig';
        } elseif (402 == $stat_type) {
            // homehelp
            $club_id = $sf->getType();
            $club = $em->getRepository('JCSGYKAdminBundle:Club')->find($club_id);
            $club_type = $club->getHomehelptype();

            $twig_tpl = HomeHelp::HELP == $club_type ? '_homehelp_stats.html.twig' : '_clubvisit_stats.html.twig';
        } else {
            $twig_tpl = null;
        }

        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';

        if ($download) {
            return $this->sendDownloadResponse($output_name, stream_get_contents($sf->getFile()), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $twig_tpl, ['data' => $data]);
        }
    }

    /**
     * Sent download headers and the file
     * @param string $file_name
     * @param string $file_contents
     * @param string $content_type
     * @return Response
     */
    public function sendDownloadResponse($file_name, $file_contents, $content_type)
    {
        $response = new Response();

        $response->headers->set('Content-Type', $content_type);
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $file_name . '"');

        $response->setContent($file_contents);

        return $response;
    }

    /**
     * Find a value in a range (from-to) and return it's key
     * @param mixed $val
     * @param array $range
     * @return int | false on failure
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

    private function getHomehelpClientsReport($form_data, $download)
    {
        $form_fields = [
            'social_worker' => null,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        $company_id = $this->ds->getCompanyId();
        $em         = $this->container->get('doctrine')->getManager();
        $ae         = $this->container->get('jcs.twig.adminextension');

        // Get the names of the social workers
        $sw = $this->ds->getSocialWorkers();

        // filter the sw list
        if (!empty($form_data['social_worker']) && isset($sw[$form_data['social_worker']])) {
            $sw = [
                $form_data['social_worker'] => $sw[$form_data['social_worker']]
            ];
        }

        $clients = [];
        foreach ($sw as $sw_id => $worker) {
            // find all the active homehelp clients
            $cli = $em->getRepository('JCSGYKAdminBundle:HomeHelp')->getClientsBySocialWorker($sw_id, $company_id);
            if (!empty($cli)) {
                $tmp = [
                    'sw' => $worker,
                    'cli' => [],
                ];
                // client rows
                foreach ($cli as $client) {
                    $doc_name = '';
                    $doc = $em->getRepository('JCSGYKAdminBundle:Client')->getRelationByType($client->getId(), Relation::DOCTOR);
                    if (!empty($doc)) {
                        $doc_name = $ae->formatClientName($doc->getParent());
                    }

                    $tmp['cli'][] = [
                        'nam' => $ae->formatClientName($client),
                        'adr' => sprintf('(%s)', $ae->formatClientAddress($client)),
                        'doc' => $doc_name,
                        'tel' => $client->getPhone() . ' ' . $client->getMobile(),
                    ];
                }
                $clients[] = $tmp;
                $clients[] = [
                    'sw' => ' ',
                    'cli' => [],
                ];
            }
        }

        $data = [
            'hh.cim'   => 'Gondozottak',
            'sp.datum' => $ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'clients' => $clients,
            ]
        ];

        $twig_tpl      = '_homehelp_clients_old.html.twig';
        $template_file = __DIR__ . '/../Resources/public/reports/homehelp_clients_old.xlsx';
        $sw_title = !empty($form_data['social_worker']) ? sprintf(' (%s)', $this->ds->get($form_data['social_worker'])) : '';

        $output_name   = $data['hh.cim'] . $sw_title . '.xlsx';

        if ($download) {
            return $this->container->get('jcs.docx')->make($template_file, $data, $output_name);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $twig_tpl, ['data' => $data]);
        }
    }

    private function getHomehelpReport($form_data, $report, $download)
    {
        $form_fields = [
            'month' => null,
        ];
        // make sure we have all required fields
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $sec        = $this->container->get('security.context');
        $ae         = $this->container->get('jcs.twig.adminextension');
        $company_id = $this->ds->getCompanyId();
        $months     = $this->container->get('jcs.invoice')->getMonths($company_id, Invoice::HOMEHELP);

        // non admins not get all clubs
        if (!$sec->isGranted('ROLE_ADMIN') && empty($form_data['club'])) {
            throw new AccessDeniedHttpException();
        }
        // get the period
        if (!isset($months[$form_data['month']])) {
            throw new AccessDeniedHttpException();
        }

        $month = new \DateTime($form_data['month']);

        if (in_array($report, ['clubvisit_days'])) {
            $report_data = $this->container->get('jcs.invoice')->getClubvisitReport($company_id, $month, $report);
        } else {
            $report_data = $this->container->get('jcs.invoice')->getHomehelpReport($company_id, $month, $report);
        }

        if ('homehelp_visits' == $report) {
            $title = sprintf('%s havi gondozási napok összesítése', $ae->formatDate($month, 'ym'));
            $template_file = __DIR__ . '/../Resources/public/reports/homehelp_visits.xlsx';
            $twig_tpl = '_homehelp_visits.html.twig';
        }
        elseif ('homehelp_hours' == $report) {
            $title = sprintf('%s havi gondozási órák összesítése', $ae->formatDate($month, 'ym'));
            $template_file = __DIR__ . '/../Resources/public/reports/homehelp_hours.xlsx';
            $twig_tpl = '_homehelp_hours.html.twig';
        }
        else {
            $title = sprintf('%s havi gondozás összesítő', $ae->formatDate($month, 'ym'));
            $template_file = __DIR__ . '/../Resources/public/reports/homehelp_summary.xlsx';
            $twig_tpl = '_homehelp_summary.html.twig';
        }

        $data = [
            'ca.cim'   => $title,
            'ca.klub'  => empty($form_data['club']) ? '' : sprintf(' (%s)', $form_data['club']->getName()),
            'sp.datum' => $ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'homehelp' => $report_data,
            ]
        ];
        if (in_array($report, ['homehelp_visits', 'homehelp_hours'])) {
            $data['blocks']['months_days'] = range(1, 31);
        }

        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';

        if ($download) {

            return $this->container->get('jcs.docx')->make($template_file, $data, $output_name);
        }
        else {

            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $twig_tpl, ['data' => $data]);
        }
    }


}