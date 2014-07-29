<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Stat;
use JCSGYK\AdminBundle\Entity\UserRepository;
use JCSGYK\AdminBundle\Entity\Client;

class ReportsController extends Controller
{
    private $reports = [];

    /** Datastore */
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
            $dl = $request->request->get('download', false);

            $result = $this->getReport($report, $form->getData(), $dl);
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
//        $company_id = $this->ds->getCompanyId();
//        $em = $this->getDoctrine();

        $form_builder = $this->createFormBuilder();
        //$form_builder->setData([]);

        if ('clients' == $report) {
            // client types (if relevant)
            if (count($client_type_list) > 1) {
                $this->getClientTypeSelect($form_builder, $client_type_list);
            }
            // admins can select the users of this company
            if ($sec->isGranted('ROLE_ADMIN')) {
                $this->getCaseAdminSelect($form_builder, false, 'nincs');
            }
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
        elseif ('catering_cashbook' == $report) {
            // day
            $this->getDayInput($form_builder);
        }
        elseif (in_array($report, ['catering_summary', 'catering_datacheck', 'catering_summary_detailed', 'catering_stats'])) {
            // month
            $this->getInvoiceMonths($form_builder);
        }
        elseif ('ksh' == $report) {
            $this->container->get('jcs.reports.ksh')->getForm($form_builder);
        }

        // club select for all catering reports
        if (in_array($report, ['catering_orders', 'catering_cashbook', 'catering_summary', 'catering_summary_detailed', 'catering_datacheck', 'catering_stats'])) {
            if ($sec->isGranted('ROLE_ADMIN')) {
                $this->getClubSelect($form_builder, false, 'mind');
            }
            else {
                // no all-clubs for non-admins
                $this->getClubSelect($form_builder);
            }
        }

        // show only debts for catering summary
        if (in_array($report, ['catering_summary', 'catering_datacheck'])) {
            $form_builder->add('only_debts', 'checkbox', [
                'label'       => 'Csak tartozások',
                'required'    => false,
                'label_attr'  => ['style' => 'float:right;margin:2px 0 0 2px;'],
            ]);
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
    private function getInvoiceMonths(&$form_builder)
    {
        $company_id = $this->ds->getCompanyId();

        $form_builder->add('month', 'choice', [
            'label'    => 'Dátum',
            'choices'  => $this->container->get('jcs.invoice')->getMonths($company_id),
            'required' => true,
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

    private function getDayInput(&$form_builder)
    {
        $form_builder->add('day', 'date', [
            'label'    => 'Dátum',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'type' => 'text'),
            'required' => true,
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

    private function getReport($report, $form_data, $download)
    {
        if ('clients' == $report) {
            return $this->getClientReport($form_data, $download);
        }
        elseif ('casecounts' == $report) {
            return $this->getCasecountsReport($form_data, $download);
        }
        elseif ('catering_orders' == $report) {
            return $this->getCateringOrderReport($form_data, $download);
        }
        elseif (in_array($report, ['catering_summary', 'catering_summary_detailed', 'catering_datacheck'])) {
            return $this->getCateringReport($form_data, $report, $download);
        }
        elseif ('catering_stats' == $report) {
            return $this->getCateringStats($form_data, $download);
        }
        elseif ('catering_cashbook' == $report) {
            return $this->getCateringCashBookReport($form_data, $download);
        }
        elseif ('ksh' == $report) {
            return $this->container->get('jcs.reports.ksh')->run($form_data, $download);
        }

        return false;
    }

    private function getMenu()
    {
        $sec = $this->container->get('security.context');
        $ds  = $this->container->get('jcs.ds');

        // role checks
        $famly_help_on    = $ds->companyHas(Client::FH) && $sec->isGranted('ROLE_FAMILY_HELP');
        $child_welfare_on = $ds->companyHas(Client::CW) && $sec->isGranted('ROLE_CHILD_WELFARE');
        $catering_on      = $ds->companyHas(Client::CA) && $sec->isGranted('ROLE_CATERING');

        $menu = [];
        if ($famly_help_on) {
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
            ];
        }
        if ($catering_on) {
            $menu['Étkeztetés'] = [
                ['slug' => 'catering_orders', 'label' => 'Heti ebédrendelések'],
                ['slug' => 'catering_cashbook', 'label' => 'Pénztárkönyv'],
                ['slug' => 'catering_summary', 'label' => 'Havi ebédösszesítő'],
                ['slug' => 'catering_summary_detailed', 'label' => 'Részletes ebédösszesítő'],
                ['slug' => 'catering_datacheck', 'label' => 'Adategyeztető'],
                ['slug' => 'catering_stats', 'label' => 'Statisztika'],
            ];
        }

        return $menu;
    }

    private function getClientReport($form_data)
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
        //$ae               = $this->container->get('jcs.twig.adminextension');
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

        $clients = $em->getRepository('JCSGYKAdminBundle:Client')->getClientsByCaseAdmin($company_id, $form_data['case_admin'], $form_data['client_type']);

        $data = [
            'blocks' => [
                'client' => $clients,
            ]
        ];
        $template_file = __DIR__ . '/../Resources/public/reports/clients.xlsx';
        $output_name   = 'ugyfel_kimutatas_' . date('Ymd') . '.xlsx';
        $send          = $this->container->get('jcs.docx')->makeReport($template_file, $data, $output_name);

        return $send;
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
        elseif ('catering_summary_detailed' == $report) {
            $title = sprintf('%s havi étkezési napok összesítése', $ae->formatDate($month, 'ym'));
            $template_file = __DIR__ . '/../Resources/public/reports/catering_summary_detailed.xlsx';
            $twig_tpl = '_summary_detailed.html.twig';
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
        if ('catering_summary_detailed' == $report) {
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

    private function getCateringCashBookReport($form_data, $download)
    {
        $form_fields = [
            'day'   => null,
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
        if (empty($form_data['day'])) {
            throw new AccessDeniedHttpException();
        }

        $date = $form_data['day']->format('Y-m-d');

        // get the payments for this date
        $sql = "SELECT i, c FROM JCSGYKAdminBundle:Invoice i LEFT JOIN i.client c LEFT JOIN c.catering a "
                . "WHERE i.companyId = :company_id AND i.payments LIKE :date ";
        if (!empty($form_data['club'])) {
            $sql .= ' AND a.club = :club ';
        }
        $sql .=  " ORDER BY c.lastname, c.firstname ";


        $q = $em->createQuery($sql)
            ->setParameter('company_id', $company_id)
            ->setParameter('date', "%$date%")
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

            foreach ($invoice->getPayments() as $payment) {
                if ($date == $payment[0]) {
                    $amount += $payment[1];
                }
            }

            $data_row = [
                'id'             => $client->getCaseLabel(),
                'name'           => $ae->formatName($client->getFirstname(), $client->getLastname(), $client->getTitle()),
                'address'        => sprintf('(%s)', $ae->formatAddress('', '', $client->getStreet(), $client->getStreetType(), $client->getStreetNumber(), $client->getFlatNumber())),
                'month'          => $this->ds->getMonth($invoice->getStartdate()->format('n')),
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

        $title = sprintf('EBÉD Pénztárkönyv %s', $ae->formatDate($form_data['day']));
        $template_file = __DIR__ . '/../Resources/public/reports/catering_cashbook.xlsx';
        $twig_tpl = '_catering_cashbook.html.twig';

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

    private function getCateringStats($form_data, $download)
    {
        $form_fields = [
            'month' => null,
            'club'  => null,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $em         = $this->container->get('doctrine')->getManager();
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
        $now = new \DateTime();

        $start_date = $month->format('Y-m-d');
        $end_date   = $month->format('Y-m-t');

        $data = [
            'ca.cim'          => sprintf('%s havi EBÉD statisztika', $ae->formatDate($month, 'ym')),
            'ca.klub'         => empty($form_data['club']) ? '' : sprintf(' (%s)', $form_data['club']->getName()),
            'sp.datum'        => $ae->formatDate(new \DateTime('today')),
            'cnum.start'      => 0,
            'cnum.new'        => 0,
            'cnum.all'        => 0,
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


        // get all the clients
        $sql = "SELECT i, c, a FROM JCSGYKAdminBundle:Invoice i LEFT JOIN i.client c LEFT JOIN c.catering a "
                . "WHERE i.companyId = :company_id AND i.startDate >= :month_start AND i.endDate <= :month_end ";
        if (!empty($form_data['club'])) {
            $sql .= ' AND a.club = :club';
        }

        $q = $em->createQuery($sql)
            ->setParameter('company_id', $company_id)
            ->setParameter('month_start', $start_date)
            ->setParameter('month_end', $end_date);
        if (!empty($form_data['club'])) {
            $q->setParameter('club', $form_data['club']);
        }
        $res = $q->getResult();

        $client_list = [];
        foreach ($res as $invoice) {
            // loop through the clients
            $client = $invoice->getClient();
            $cid = $client->getId();
            if (!in_array($cid, $client_list)) {
                $client_list[] = $cid;

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
            // loop through the invoices
            $all_items = json_decode($invoice->getItems(), true);
            //var_dump($items);
            foreach ($all_items as $items) {
                $data['inv.days'] += $items['quantity'];
                if ($items['unit_price'] == 0) {
                    if (!empty($items['weekday_quantity'])) {
                        $data['inv.discweek'] += $items['weekday_quantity'];
                        $data['inv.discweekcli'][$cid] = 1;
                    }
                    if ($items['quantity'] > $items['weekday_quantity']) {
                        $data['inv.discend'] += $items['quantity'] - $items['weekday_quantity'];
                        $data['inv.discendcli'][$cid] = 1;
                    }
                }
                else {
                    if (!empty($items['weekday_quantity'])) {
                        $data['inv.payweek'] += $items['weekday_quantity'];
                        $data['inv.payweekcli'][$cid] = 1;
                    }
                    if ($items['quantity'] > $items['weekday_quantity']) {
                        $data['inv.payend'] += $items['quantity'] - $items['weekday_quantity'];
                        $data['inv.payendcli'][$cid] = 1;
                    }
                }
                $data['inv.sum'] += $invoice->getAmount();
            }
        }
        // get only the client counts
        $data['inv.discweekcli'] = count($data['inv.discweekcli']);
        $data['inv.discendcli'] = count($data['inv.discendcli']);
        $data['inv.payweekcli'] = count($data['inv.payweekcli']);
        $data['inv.payendcli'] = count($data['inv.payendcli']);

        $data['cnum.all'] = $data['cnum.start'] + $data['cnum.new'];
        $data['cnum.end'] = $data['cnum.all'] - $data['cnum.archived'];

        $data['inv.sum'] = $ae->formatCurrency($data['inv.sum']);

        $template_file = __DIR__ . '/../Resources/public/reports/catering_stats.xlsx';
        $twig_tpl = '_catering_stats.html.twig';

        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';

        if ($download) {

            return $this->container->get('jcs.docx')->make($template_file, $data, $output_name);
        }
        else {

            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $twig_tpl, ['data' => $data]);
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
}