<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Stat;
use JCSGYK\AdminBundle\Entity\UserRepository;

class ReportsController extends Controller
{
    private $reports = [];

    /**
    * @Secure(roles="ROLE_USER")
    */
    public function indexAction($report = null)
    {
        // check if user has access to the selected report
        $this->checkRole($report);

        $result = null;
        $request = $this->getRequest();

        // get the form for the selected report
        $form = $this->getReportForm($report);

        // render and download reports
        if ($request->isMethod('POST')) {
            $form->bind($request);

            $result = $this->getReport($report, $form->getData());
        }

        return $this->render('JCSGYKAdminBundle:Reports:index.html.twig', [
            'menu' => $this->getMenu(),
            'report' => $report,
            'result' => $result,
            'form' => $form ? $form->createView() : null,
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
        $sec = $this->container->get('security.context');

        $menu = $this->getMenu();
        foreach ($menu as $item) {
            if ($item['slug'] == $report) {
                $role_needed = $item['role'];
                if ($sec->isGranted($role_needed)) {
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

        $sec = $this->container->get('security.context');
        $ds = $this->container->get('jcs.ds');
        $client_type_list = $ds->getClientTypes();
//        $company_id = $ds->getCompanyId();
//        $em = $this->getDoctrine();

        $form_builder = $this->createFormBuilder();
        //$form_builder->setData([]);

        if ('clients' == $report) {
            // client types (if relevant)
            if (count($client_type_list) > 1) {
                $this->getClientTypeSelect($form_builder, $client_type_list);
            }
            // admins can select the users of this company
            if($sec->isGranted('ROLE_ADMIN')) {
                $this->getCaseAdminSelect($form_builder, $ds, false, 'nincs');
            }
        }
        elseif ('casecounts' == $report) {
            // client types (if relevant)
            if (count($client_type_list) > 1) {
                $this->getClientTypeSelect($form_builder, $client_type_list);
            }
            // admins can select the users of this company
            if($sec->isGranted('ROLE_ADMIN')) {
                $this->getCaseAdminSelect($form_builder, $ds, false, 'mind');
            }
        }

        return $form_builder->getForm();
    }

    private function getClientTypeSelect(&$form_builder, $client_type_list)
    {
        $form_builder->add('client_type', 'choice', [
            'label' => 'Ügyfél típus',
            'choices' => $client_type_list,
            'required' => false,
            'empty_value' => 'összes',
        ]);
    }

    private function getCaseAdminSelect(&$form_builder, $ds, $required = true, $empty_value = '')
    {
        $form_builder->add('case_admin', 'entity', [
            'label' => 'Esetgazda',
            'class' => 'JCSGYKAdminBundle:User',
            'choices' => $ds->getUsers(),
            'required' => $required,
            'empty_value' => $empty_value,
       ]);
    }

    private function getReport($report, $form_data)
    {
        if ('clients' == $report) {
            return $this->getClientReport($form_data);
        }
        elseif ('casecounts' == $report) {
            return $this->getCasecountsReport($form_data);
        }

        return false;
    }

    private function getMenu()
    {
        $menu = [
            ['slug' => 'clients', 'label' => 'Ügyfelek', 'role' => 'ROLE_USER'],
            ['slug' => 'casecounts', 'label' => 'Esetszámok', 'role' => 'ROLE_USER'],
        ];

        return $menu;
    }

    private function getClientReport($form_data)
    {
        $form_fields = [
            'case_admin' => null,
            'client_type' => null,
        ];
        // make sure we have all required fields
        $form_data = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $em = $this->getDoctrine();
        $sec = $this->container->get('security.context');
        $ds = $this->container->get('jcs.ds');
        $ae = $this->container->get('jcs.twig.adminextension');
        $client_type_list = $ds->getClientTypes();
        $client_type_keys = array_keys($client_type_list);
        $company_id = $ds->getCompanyId();

        // non admins can only see their own clients
        if(!$sec->isGranted('ROLE_ADMIN')) {
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
        $template_file = __DIR__.'/../Resources/public/reports/clients.xlsx';
        $output_name = 'ugyfel_kimutatas_' . date('Ymd') . '.xlsx';
        $send = $this->container->get('jcs.docx')->makeReport($template_file, $data, $output_name);

        return $send;
    }

    private function getCasecountsReport($form_data)
    {
        $form_fields = [
            'case_admin' => null,
            'client_type' => null,
        ];
        // make sure we have all required fields
        $form_data = array_merge($form_fields, $form_data);

        // check for user roles and set the params accordingly
        $em = $this->getDoctrine();
        $sec = $this->container->get('security.context');
        $ds = $this->container->get('jcs.ds');
        $ae = $this->container->get('jcs.twig.adminextension');
        $client_type_list = $ds->getClientTypes();
        $client_type_keys = array_keys($client_type_list);
        $company_id = $ds->getCompanyId();

        // non admins can only see their own clients
        if(!$sec->isGranted('ROLE_ADMIN')) {
            $form_data['case_admin'] = $sec->getToken()->getUser()->getId();
        }
        // make sure the client type is correct
        if (count($client_type_keys) < 2 && !in_array($form_data['client_type'], $client_type_keys)) {
            $form_data['client_type'] = reset($client_type_keys);
        }

        $counts = $em->getRepository('JCSGYKAdminBundle:Client')->getCaseCounts($company_id, $form_data['case_admin'], $form_data['client_type']);

        $data = [
            'blocks' => [
                'casecount' => $counts,
            ]
        ];
        $template_file = __DIR__.'/../Resources/public/reports/casecounts.xlsx';
        $output_name = 'esetszam_kimutatas_' . date('Ymd') . '.xlsx';
        $send = $this->container->get('jcs.docx')->makeReport($template_file, $data, $output_name);

        return $send;
    }
}