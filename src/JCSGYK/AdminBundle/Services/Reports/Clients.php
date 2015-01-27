<?php

namespace JCSGYK\AdminBundle\Services\Reports;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Clients Report Service
 */
class Clients
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
    private $template = 'clients.xlsx';
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

        $this->template = __DIR__ . '/../../Resources/public/reports/' . $this->template;
        $this->output   = sprintf($this->output, date('Y-m-d'));
    }

    public function getForm(&$form_builder)
    {
        $sec              = $this->container->get('security.context');
        $client_type_list = $this->ds->getClientTypes();

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
        if ($sec->isGranted('ROLE_ADMIN')) {
            $form_builder->add('case_admin', 'entity', [
                'label'       => 'Esetgazda',
                'class'       => 'JCSGYKAdminBundle:User',
                'choices'     => $this->ds->getCaseAdmins(null, false),
                'required'    => false,
                'empty_value' => 'nincs',
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

    public function run($form_data, $download)
    {
        $form_data = $this->checkFormData($form_data);
        $title = $this->getReportTitle($form_data);

        $company_id = $this->ds->getCompanyId();

        $clients = $this->em->getRepository('JCSGYKAdminBundle:Client')->getClientsForReport($company_id, $form_data);

        $this->data = [
            'rep.title' => $title,
            'blocks' => [
                'client' => $clients,
            ]
        ];

        return $this->output($download);
    }

    /**
     * Make sure that all settings in the filter are correct
     *
     * @param array $form_data
     * @return array
     */
    private function checkFormData($form_data)
    {
        $form_fields = [
            'case_admin'  => null,
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
        $client_type_keys = array_keys($client_type_list);
        if (count($client_type_keys) < 2 && !in_array($form_data['client_type'], $client_type_keys)) {
            $form_data['client_type'] = reset($client_type_keys);
        }

        // substitute the age range
        if (!empty($form_data['birth_date'])) {
            if (!isset($this->ageRanges[$form_data['birth_date']])) {
                $form_data['birth_date'] = null;
            } else {
                $form_data['birth_date'] = $this->ageRanges[$form_data['birth_date']];
            }
        }

        return $form_data;
    }

    private function getReportTitle($form_data)
    {
        $re = ['Ügyfél kimutatás'];
        if (empty($form_data['case_admin'])) {
            $re[] = 'nincs esetgazda';
        } else {
            $case_admin = $this->em->getRepository('JCSGYKAdminBundle:User')->find($form_data['case_admin']);
            if (!empty($case_admin)) {
                $re[] = $this->ae->formatName($case_admin->getFirstname(), $case_admin->getLastname());
            }
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
     * Generates the output with OpenTBS
     * @return type
     */
    public function output($download)
    {
        if ($download) {
            return $this->container->get('jcs.docx')->makeReport($this->template, $this->data, $this->output);
        }
        else {
            return $this->container->get('templating')->render('JCSGYKAdminBundle:Reports:_clients.html.twig', ['data' => $this->data]);
        }
    }
}