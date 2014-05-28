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

            $result = $this->getReport($report, $form->getData());
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
        foreach ($menu as $item) {
            if ($item['slug'] == $report) {
                if ($item['on']) {
                    $granted = true;
                }
                break;
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
            // month
            $this->getCateringWeeks($form_builder);
        }
        elseif ('catering_cashbook' == $report) {
            // month
            $this->getInvoiceMonths($form_builder);
        }
        elseif ('catering_summary' == $report) {
            // month
            $this->getInvoiceMonths($form_builder);
        }

        // club select for all catering reports
        if (in_array($report, ['catering_orders', 'catering_cashbook', 'catering_summary'])) {
            if ($sec->isGranted('ROLE_ADMIN')) {
                $this->getClubSelect($form_builder, false, 'mind');
            }
            else {
                // no all-clubs for non-admins
                $this->getClubSelect($form_builder);
            }
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

    private function getReport($report, $form_data)
    {
        if ('clients' == $report) {
            return $this->getClientReport($form_data);
        }
        elseif ('casecounts' == $report) {
            return $this->getCasecountsReport($form_data);
        }
        elseif ('catering_orders' == $report) {
            return $this->getCateringOrderReport($form_data);
        }
        elseif ('catering_cashbook' == $report || 'catering_summary' == $report) {
            return $this->getCateringReport($form_data, $report);
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

        $menu = [
            ['slug' => 'clients', 'label' => 'Ügyfelek', 'on' => $famly_help_on || $child_welfare_on],
            ['slug' => 'casecounts', 'label' => 'Esetszámok', 'on' => $famly_help_on || $child_welfare_on],
            ['slug' => 'catering_orders', 'label' => 'Heti ebédrendelések', 'on' => $catering_on],
            ['slug' => 'catering_cashbook', 'label' => 'Pénztárkönyv', 'on' => $catering_on],
            ['slug' => 'catering_summary', 'label' => 'Havi ebédösszesítő', 'on' => $catering_on],
        ];

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

    private function getCateringOrderReport($form_data)
    {
        $form_fields = [
            'week' => null,
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
        $end_date = clone $start_date;
        $end_date = $end_date->modify('+ 6 days');

        $menus = $this->ds->getGroup('lunch_types');
        // get only the first character from the menu names
        foreach ($menus as $m_id => $menu) {
            $new_name = '';
            $name_parts = explode(' ', $menu);
            $new_name .= end($name_parts)[0];
            $menus[$m_id] = $new_name;
        }

        $res = $em->createQuery("SELECT o, c.id, c.title, c.lastname, c.firstname, c.socialSecurityNumber, c.zipCode, c.city, c.street, c.streetType, c.streetNumber, c.flatNumber"
                . " FROM JCSGYKAdminBundle:ClientOrder o LEFT JOIN o.client c"
                . " WHERE o.companyId = :company_id AND o.date >= :start_date AND o.date <= :end_date"
                . " ORDER BY c.lastname, c.firstname, o.date")
            ->setParameter('company_id', $company_id)
            ->setParameter('start_date', $start_date->format('Y-m-d'))
            ->setParameter('end_date', $end_date->format('Y-m-d'))
            ->getResult();

        $report_data = [];
        foreach ($res as $rec) {
            if (empty($report_data[$rec['id']])) {
                $report_data[$rec['id']] = [
                    'ssn' => $rec['socialSecurityNumber'],
                    'name' => $ae->formatName($rec['firstname'], $rec['lastname'], $rec['title']),
                    'address' => $ae->formatAddress('', '', '', $rec['street'], $rec['streetType'], $rec['streetNumber'], $rec['flatNumber']),
                    1 => '',
                    2 => '',
                    3 => '',
                    4 => '',
                    5 => '',
                    6 => '',
                    7 => '',
                ];
            }
            $day_num = $rec[0]->getDate()->format('N');
            $o = '';
            if ($rec[0]->getOrder()) {
                if ($rec[0]->getCancel()) {
                    $o = '-';
                }
                else {
                    $o = isset($menus[$rec[0]->getMenu()]) ? $menus[$rec[0]->getMenu()] : '?';
                }
            }

            $report_data[$rec['id']][$day_num] = $o;
        }

        $data = [
            'ca.cim'   => sprintf('%s. %s heti ebéd rendelések', $start_date->format('Y'), $start_date->format('W')),
            'ca.klub'  => empty($form_data['club']) ? '' : sprintf(' (%s)', $form_data['club']->getName()),
            'sp.datum' => $ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'catering' => $report_data,
            ]
        ];

        $template_file = __DIR__ . '/../Resources/public/reports/catering_orders.xlsx';
        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';
        $send          = $this->container->get('jcs.docx')->make($template_file, $data, $output_name);

        return $send;
    }

    private function getCateringReport($form_data, $report)
    {
        $form_fields = [
            'month' => null,
            'club'  => null,
        ];
        // make sure we have all required fields
        // 'club' => Club entity or null for all
        $form_data   = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $em         = $this->getDoctrine();
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

        $report_data = $this->container->get('jcs.invoice')->getCateringReport($company_id, $month, $form_data['club']);

        $data = [
            'ca.cim'   => sprintf('%s havi ebéd összesítő', $ae->formatDate($month, 'ym')),
            'ca.klub'  => empty($form_data['club']) ? '' : sprintf(' (%s)', $form_data['club']->getName()),
            'sp.datum' => $ae->formatDate(new \DateTime('today')),
            'blocks'   => [
                'catering' => $report_data,
            ]
        ];

        $template_file = __DIR__ . '/../Resources/public/reports/catering.xlsx';
        $output_name   = $data['ca.cim'] . $data['ca.klub'] . '.xlsx';
        $send          = $this->container->get('jcs.docx')->make($template_file, $data, $output_name);

        return $send;
    }

}