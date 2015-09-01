<?php

namespace JCSGYK\AdminBundle\Services\Reports;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Twig\AdminExtension;
use Doctrine\ORM\EntityManager;

/**
 * Clients Report Service
 */
class Clients
{
    /** Service container */
    private $container;
    /** @var DataStore */
    private $ds;
    /** @var AdminExtension Twig formatter */
    private $ae;
    /** @var EntityManager Doctrine Entity Manager */
    private $em;

    /** reports data */
    private $data = [];
    /** OpenTBS template file */
    private $template = 'clients.xlsx';
    /** Twig template file */
    private $twigTemplate = '_clients.html.twig';
    /** Output file name */
    private $output = 'ugyfel_kimutatas_%s.xlsx';

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
    }

    public function getForm(&$form_builder, $report)
    {
        $sec              = $this->container->get('security.context');
        $client_type_list = $this->ds->getClientTypes();
        $client_types = array_keys($client_type_list);

        // client types (if relevant)
        if (count($client_type_list) > 1) {
            $form_builder->add('client_type', 'choice', [
                'label'       => 'Ügyfél típus',
                'choices'     => $client_type_list,
                'required'    => false,
                'empty_value' => 'összes',
            ]);
        }
        // admins can select the users of this company
        if ($sec->isGranted('ROLE_ADMIN') && (in_array(Client::FH, $client_types) || in_array(Client::CW, $client_types))) {
            $caseAdmins = $this->ds->getCaseAdmins(null, false);
            $caseAdmins = $this->ds->convertCaseAdminsToString($caseAdmins);
            array_unshift($caseAdmins, 'nincs');
            $form_builder->add('case_admin', 'choice', [
                'label'       => 'Esetgazda',
                'choices'     => $caseAdmins,
                'required'    => false,
                'empty_value' => '',
            ]);
        }
        if (in_array(Client::CA, $client_types) && in_array($report, ['catering_clients', 'clubvisit_clients'])) {
            // clubs
            $form_builder->add('club', 'entity', [
                'label'    => 'Klub',
                'class'    => 'JCSGYKAdminBundle:Club',
                'choices'  => $this->ds->getClubs(),
                'required' => true,
            ]);
        }

        // Is Archived
        $form_builder->add('is_archived', 'choice', [
            'label'       => 'Státus',
            'choices'     => [
                0 => 'Aktív',
                1 => 'Archivált',
            ],
            'required'    => false,
            'empty_value' => '',
        ]);
        // Age
        $ages = [];
        foreach ($this->ageRanges as $key => $age) {
            $ages[$key] = sprintf ("%s - %s -ig", $age[0], $age[1]);
        }
        $form_builder->add('birth_date', 'choice', [
            'label'       => 'Életkor',
            'choices'     => $ages,
            'required'    => false,
            'empty_value' => '',
        ]);
    }

    public function run($form_data, $report, $download)
    {
        if (in_array($report, ['catering_clients'])) {
            $this->twigTemplate = '_catering_clients.html.twig';
            $this->template = 'catering_clients.xlsx';
            $this->output = 'etkeztetes_ugyfel_kimutatas_%s.xlsx';
        } elseif (in_array($report, ['clubvisit_clients', 'homehelp_clients'])) {
            $this->twigTemplate = '_homehelp_clients.html.twig';
            $this->template = 'homehelp_clients.xlsx';
            $this->output = 'gondozas_ugyfel_kimutatas_%s.xlsx';
        }

        $this->template = __DIR__ . '/../../Resources/public/reports/' . $this->template;
        $this->output   = sprintf($this->output, date('Y-m-d'));


        $form_data = $this->checkFormData($form_data, $report);
        $title = $this->getReportTitle($form_data, $report);

        $company_id = $this->ds->getCompanyId();

        $clients = $this->em->getRepository('JCSGYKAdminBundle:Client')->getClientsForReport($company_id, $form_data, $report);

        $this->data = [
            'rep.title' => $title,
            'blocks' => [
                'client' => $clients,
            ]
        ];
        // if we select all clients, we can not display the problems
        $reportWithProblems = (is_null($form_data['case_admin'])) ? false : true;

        return $this->output($download, $reportWithProblems);
    }

    /**
     * Make sure that all settings in the filter are correct
     *
     * @param array $form_data
     * @return array
     */
    private function checkFormData($form_data, $report)
    {
        $form_fields = [
            'case_admin'  => false,
            'club'        => null,
            'client_type' => null,
            'is_archived' => null,
            'birth_date'  => null,
        ];
        // make sure we have all required fields
        $form_data = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $sec = $this->container->get('security.context');
        // non admins can only see their own clients
        if (!$sec->isGranted('ROLE_ADMIN')) {
            $form_data['case_admin'] = $sec->getToken()->getUser()->getId();
        }

        // make sure the client type is correct
        $client_type_list = $this->ds->getClientTypes();
        $client_types = array_keys($client_type_list);
        if (count($client_types) < 2 && !in_array($form_data['client_type'], $client_types)) {
            $form_data['client_type'] = reset($client_types);
        }

        // substitute the age range
        if (!empty($form_data['birth_date'])) {
            if (!isset($this->ageRanges[$form_data['birth_date']])) {
                $form_data['birth_date'] = null;
            } else {
                $form_data['birth_date'] = $this->ageRanges[$form_data['birth_date']];
            }
        }

        // homehelp clients can only come from club id 1
        if ('homehelp_clients' == $report) {
            $club = $this->em->getRepository('JCSGYKAdminBundle:Club')->findBy(['homehelptype' => 0]);
            $form_data['club'] = $club[0];
        }

        return $form_data;
    }

    private function getReportTitle($form_data, $report)
    {

        if ('catering_clients' == $report) {
            $re = ['Étkeztetés ügyfelek kimutatása'];
        } elseif ('homehelp_clients' == $report) {
            $re = ['Gondozási ügyfelek kimutatása'];
        } elseif ('clubvisit_clients' == $report) {
            $re = ['Látogatási ügyfelek kimutatása'];
        } else {
            $re = ['Ügyfél kimutatás'];
        }

        if (false !== $form_data['case_admin']) {
            if (is_null($form_data['case_admin'])) {
                $re[] = 'nincs esetgazda';
            } else {
                $case_admin = $this->em->getRepository('JCSGYKAdminBundle:User')->find($form_data['case_admin']);
                if (!empty($case_admin)) {
                    $re[] = $this->ae->formatName($case_admin->getFirstname(), $case_admin->getLastname());
                }
            }
        }

        if (!empty($form_data['club'])) {
            $re[] = $form_data['club'];
        }

        if (!is_null($form_data['is_archived'])) {
            $re[] = $form_data['is_archived'] ? 'archivált' : 'aktív';
        }

        if (!is_null($form_data['birth_date'])) {
            $re[] = sprintf('%s-%s-évig', $form_data['birth_date'][0], $form_data['birth_date'][1]);
        }

        return implode(' - ', $re);
    }

    /**
     * Generates the output with Twig or OpenTBS
     * @param $download
     * @param bool $reportWithProblems
     * @return type
     */
    public function output($download, $reportWithProblems = true)
    {
        if ($download) {
            return $this->container->get('jcs.docx')->makeReport($this->template, $this->data, $this->output);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:' . $this->twigTemplate, ['data' => $this->data, 'with_problems' => $reportWithProblems]);
        }
    }
}