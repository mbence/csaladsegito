<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use JCSGYK\AdminBundle\Entity\HomehelpMonth;

class HomehelpController extends Controller
{
    /**
     * Admin Homehelp table editor
     *
     * @param Request $request
     * @param null $social_worker
     * @param string $month
     * @return array|Response
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/home_help/{social_worker}/{month}", name="admin_home_help")
     * @Template("JCSGYKAdminBundle:Admin:homehelp.html.twig")
     */
    public function homehelpAction(Request $request, $social_worker = null, $month = null)
    {
        $ds = $this->container->get('jcs.ds');
        $user = $ds->getUser();
        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        $clients = $this->getSocialWorkersClients($social_worker);

        if (empty($month)) {
            $month = 'first day of this month';
        }
        $month = (new \DateTime($month))->setTime(0, 0, 0);

        // get the record from db
        $hh_month = $this->getHHMonth($social_worker, $month);

        // creat a new if no record found
        if (empty($hh_month)) {
            //$this->fillEmptyHHTable($clients, $month, $table_data, $row_headers);
            // create new record
            $hh_month = (new HomehelpMonth())
                ->setCompanyId($ds->getCompanyId())
                ->setSocialWorker($social_worker)
                ->setDate($month)
                ->setRowheaders([])
                ->setData([])
            ;

            foreach ($clients as $client) {
                $hh_month->addClient($client);
            }
        }
        // check and fill the client block
        $hh_month = $this->hhCheckClientBlock($hh_month);
        $hh_month = $this->hhCheckServicesBlocks($hh_month);

        // we need the row headers later
        $row_headers = $hh_month->getRowheaders();



        $form = $this->homeHelpForm($hh_month);
        $form->handleRequest($request);

        if ($form->isValid() && $form->get('hh_id')->getData() == $hh_month->getId()) {
            // table data from the form field
            $hh_data = json_decode($form->get('value')->getData(), true);
            if (empty($hh_data)) {
                $hh_data = [];
            }

            // update the clients
            $client_repo = $em->getRepository('JCSGYKAdminBundle:Client');

            // remove clients
            $clients_to_remove = json_decode($form->get('to_remove')->getData(), true);
            if (is_array($clients_to_remove) && !empty($clients_to_remove)) {
                // get the clients of this social worker
                $my_clients = $em->getRepository('JCSGYKAdminBundle:HomeHelp')->getClientsBySocialWorker($social_worker, $ds->getCompanyId(), true, true);
                foreach ($clients_to_remove as $client_id) {
                    // make sure we never remove our own clients
                    if (!in_array($client_id, $my_clients)) {
                        // remove the relation
                        $hh_month->removeClient($client_repo->find($client_id));
                    }
                }
            }

            // add the new clients
            $clients_to_add = json_decode($form->get('to_add')->getData(), true);
            if (is_array($clients_to_add) and !empty($clients_to_add)) {
                foreach ($clients_to_add as $client_id) {
                    // add the relation
                    $nc = $client_repo->find($client_id);
                    $hh_month->addClient($nc);
                }
            }
            // the rest of the modifications will be taken care of in the $this->hhCheckClientBlock()

            // save the table
            $hh_month->setData($hh_data);
            $hh_month->setRowheaders($row_headers);

            // run checks again with the new records
            $hh_month = $this->hhCheckClientBlock($hh_month);
            $hh_month = $this->hhCheckServicesBlocks($hh_month);

            if (empty($hh_month->getId())) {
                $hh_month->setCreatedBy($user->getId());
                $hh_month->setCreatedAt(new \DateTime());
                $em->persist($hh_month);
            }
            else {
                $hh_month->setModifiedBy($user->getId());
                $hh_month->setModifiedAt(new \DateTime());
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Gondozás elmentve');

            return $this->redirect(
                $this->generateUrl('admin_home_help', [
                    'social_worker' => $social_worker,
                    'month'         => $month->format('Y-m')
                ])
            );
        }
//        var_dump($table_data);

        return [
            'form'           => $form->createview(),
            'filter_form'    => $this->homeHelpFilter($hh_month)->createView(),
            'table_defaults' => $this->getHomehelpDefaults($month, $row_headers),
            'hh_weekends'    => $this->getHomehelpWeekends($month),
        ];
    }

    /**
     * Return the month record for this Social Worker
     * @param $social_worker
     * @param \DateTime $month
     * @return HomehelpMonth
     */
    private function getHHMonth($social_worker, \DateTime $month)
    {
        $ds = $this->container->get('jcs.ds');
        $em = $this->container->get('doctrine')->getManager();

        return $em->createQuery("SELECT m FROM JCSGYKAdminBundle:HomehelpMonth m WHERE m.companyId = :company AND m.socialWorker = :sw AND m.date = :month")
            ->SetParameter('company', $ds->getCompanyId())
            ->SetParameter('sw', $social_worker)
            ->SetParameter('month', $month)
            ->getOneOrNullResult();
    }

    /**
     * Filter form for the home help editor
     *
     * @param HomehelpMonth $hh_month
     * @return \Symfony\Component\Form\Form
     */
    private function homeHelpFilter(HomehelpMonth $hh_month)
    {
        $ds = $this->container->get('jcs.ds');
        $ae = $this->container->get('jcs.twig.adminextension');

        $defaults = [
            'social_worker' => $hh_month->getSocialWorker(),
            'month'         => $hh_month->getDate()->format('Y-m')
        ];

        // add the months for a year before
        $months = [];
        $m = new \DateTime('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $months[$m->format('Y-m')] = $ae->formatDate($m, 'ym');
            $m->modify('+1 month');
        }

        // build the filter form
        $form_builder = $this->createFormBuilder($defaults)
            // final url will be set by the js function "setupHomehelp" in jcssettings.coffee
            ->setAction($this->generateUrl('admin_home_help'))
            ->setMethod('GET')
            // add the social workers
            ->add('social_worker', 'choice', [
                'label'   => 'Gondozó',
                'choices' => $ds->getSocialWorkers(),
            ])
            ->add('month', 'choice', [
                'label'   => 'Hónap',
                'choices' => $months,
            ])
        ;

        return $form_builder->getForm();
    }

    /**
     * Get the main form for the Home Help editor
     *
     * @param HomehelpMonth $hh_month
     * @return \Symfony\Component\Form\Form
     */
    private function homeHelpForm(HomehelpMonth $hh_month)
    {
        // build the form
        $form_builder = $this->createFormBuilder(['hh_id' => $hh_month->getId() , 'value' => json_encode($hh_month->getData())])
            ->setAction($this->generateUrl('admin_home_help', ['social_worker' => $hh_month->getSocialWorker(), 'month' => $hh_month->getDate()->format('Y-m')]))
            ->setMethod('POST')
            ->add('hh_id', 'hidden')
            ->add('value', 'hidden')
            ->add('to_add', 'hidden')
            ->add('to_remove', 'hidden')
        ;

        return $form_builder->getForm();
    }

    /**
     * Get the default settings for the home help handsontable
     *
     * @param \DateTime $month
     * @param array $row_headers
     * @return string  json encoded array
     */
    private function getHomehelpDefaults(\DateTime $month, array $row_headers)
    {
        $day_count = $month->format('t');

        $re = [
            'minSpareRows'          => 0,
            'cells'                 => true, // use the cells function in Jcssettings to format the table nicely
            'sums'                  => true, // use the sums function in Jcssettings to refresh the sums
            'readOnlyCellClassName' => 'hh-readonly',
            'colWidths'             => [],
            'colHeaders'            => [],
            'rowHeaders'            => $row_headers,
            'columns'               => [],
        ];
        for ($d = 1; $d <= $day_count; $d++) {
            $re['columns'][]    = [
                'data'     => $d,
                'type'     => 'text',
                'language' => 'hu',
            ];
            $re['colHeaders'][] = $d;
            $re['colWidths'][]  = 24;
        }
        // add totals column
        $re['colHeaders'][] = 'Össz.';
        $re['colWidths'][]  = 35;
        $re['columns'][]    = [
            'data'     => $d,
            'type'     => 'text',
            'language' => 'hu',
            'readonly' => true,
        ];
        // add visits col
        $re['colHeaders'][] = 'Lát.';
        $re['colWidths'][]  = 35;
        $re['columns'][]    = [
            'data'     => $d + 1,
            'type'     => 'text',
            'language' => 'hu',
            'readonly' => true,
        ];

        return json_encode($re);
    }

    /**
     * Find the weekends of this month
     *
     * @param \DateTime $month
     * @return string json encoded array
     */
    private function getHomehelpWeekends(\DateTime $month)
    {
        $weekends = [];
        $day = new \DateTime($month->format('Y-m-01'));
        $end = new \DateTime($month->format('Y-m-t'));
        $holidays = $this->container->get('jcs.ds')->getHolidays($day->format('Y-m-d'), $end->format('Y-m-d'));

        while ($day <= $end) {
            if ($day->format('N') > 5) {
                $weekends[] = (int) $day->format('d');
            }
            $day->modify('+1 day');
        }
        // add the holidays
        foreach ($holidays as $date => $type) {
            if ($type != 2) {
                $weekends[] = (int) substr($date, 8);
            }
        }

        return json_encode($weekends);
    }

    /**
     * Find the clients of the selected social worker
     * Also update the $social_worker to the first of the list if none selected
     *
     * @param $social_worker
     * @internal param $clients
     * @return array list of clients
     */
    private function getSocialWorkersClients(&$social_worker)
    {
        $ds = $this->container->get('jcs.ds');
        $em = $this->container->get('doctrine')->getManager();

        // if no sw provided, select the first from the list
        if (empty($social_worker)) {
            $social_workers = $ds->getSocialWorkers();
            reset($social_workers);
            $social_worker = key($social_workers);
        }
        // find the clients of this social worker
        $clients = [];
        if (!empty($social_worker)) {
            $clients = $em->getRepository('JCSGYKAdminBundle:HomeHelp')->getClientsBySocialWorker($social_worker, $ds->getCompanyId());
        }

        return $clients;
    }

    /**
     * Check the client block in the homehelp table
     * Fill the block if empty, reorder the clients by name, and recalculate the summary cells
     *
     * @param HomehelpMonth $hh_month
     * @return \JCSGYK\AdminBundle\Entity\HomehelpMonth
     */
    private function hhCheckClientBlock(HomehelpMonth $hh_month)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        $day_count = (int) $hh_month->getDate()->format('t');

        $clients = $hh_month->getClients();
        $table_data = $hh_month->getData();
        $row_headers = $hh_month->getRowheaders();

        // get the individual client rows
        if (!empty($table_data)) {
            // first find the client rows
            $client_rows = [];
            // the first few rows are the client rows followed by a sum and a null row
            while (!empty($table_data[0][0]) && 'sum' != $table_data[0][0]) {
                $row = array_shift($table_data);
                $client_rows[$row[0]] = $row;
                array_shift($row_headers);
            }
            // now the homehelp table has no client rows, remove the summary row as well
            if (!empty($table_data[0][0]) && 'sum' == $table_data[0][0]) {
                array_shift($table_data);
                array_shift($row_headers);
            }
            // lets clear the separator row too
            if (is_null($table_data[0])) {
                array_shift($table_data);
                array_shift($row_headers);
            }
            // now the client block is completely removed, and the client data is in $client_rows
        }

        // build the client list
        $client_list = [];
        foreach ($clients as $client) {
            $client_list[$client->getId()] = $ae->formatClientName($client);
        }
        // order the clients by name
        $collator = new \Collator('hu_HU');
        $collator->asort($client_list);

        // build the client rows
        $rows = [];
        $headers = [];
        foreach ($client_list as $client_id => $client_name) {
            if (isset($client_rows[$client_id])) {
                $row = $client_rows[$client_id];
                unset($client_rows[$client_id]);
            } else {
                $row = [$client_id] + array_fill(1, $day_count + 2, '');
            }
            $rows[] = $row;
            $headers[] = $client_name;
        }

        // calculate the summary fields and clean the whole block
        $this->hhSums($rows, $headers, $day_count, true);
        $this->hhAddSeparatorRow($rows, $headers);

        // done building the rows, lets insert in the table
        $table_data = array_merge($rows, $table_data);
        $row_headers = array_merge($headers, $row_headers);

        $hh_month->setData($table_data);
        $hh_month->setRowheaders($row_headers);

        return $hh_month;
    }

    /**
     * Add separator row;
     *
     * @param $rows
     * @param $headers
     */
    private function hhAddSeparatorRow(&$rows, &$headers)
    {
        $rows[] = null;
        $headers[] = '';
    }

    /**
     * Cleanup the rows (remove 0-s, convert numbers to float)
     *
     * @param array $rows
     * @return array
     */
    private function hhCleanRows($rows) {
        foreach ($rows as &$row) {
            foreach ($row as &$v) {
                if (empty($v)) {
                    $v = '';
                }
                elseif (is_numeric($v)) {
                    $v = (float) $v;
                }
            };
        }

        return $rows;
    }

    /**
     * Calculate the summary fields and sum row
     *
     * @param array $rows
     * @param array $headers
     * @param int $day_count
     * @param bool $visits
     */
    private function hhSums(&$rows, &$headers, $day_count, $visits = false)
    {
        $sum_row = ['sum'] + array_fill(1, $day_count + 2, 0);
        $sum_col = $day_count + 1;
        $visit_col = $day_count + 2;

        foreach ($rows as &$row) {
            // row sum and visits
            $sum = 0;
            $visit_sum = 0;
            foreach ($row as $k => $v) {
                if ($k > 0 && $k <= $day_count) {
                    if (!empty($v)) {
                        if ($visits) {
                            $visit_sum++;
                        }
                        if (is_numeric($v)) {
                            $sum += $v;
                        }
                    }
                }
                // add the sums
                if ($k > 0 && !empty($v) && is_numeric($v)) {
                    $sum_row[$k] += $v;
                }
            }
            $row[$sum_col] = $sum;
            $row[$visit_col] = $visit_sum;
        }
        $rows[] = $sum_row;

        // sums row header
        $headers[] = 'összesen';

        $rows = $this->hhCleanRows($rows);
    }

    /**
     * Check the service blocks in the homehelp table
     * Fill the block if empty and recalculate the summary cells
     *
     * @param HomehelpMonth $hh_month
     * @return HomehelpMonth
     */
    private function hhCheckServicesBlocks(HomehelpMonth $hh_month)
    {
        $client_count = count($hh_month->getClients());
        $day_count = (int)$hh_month->getDate()->format('t');

        $table_data = $hh_month->getData();
        $row_headers = $hh_month->getRowheaders();

        $rows = [];
        $service_headers = ['közlekedés', 'vásárlás', 'ügyintézés', 'központ', 'közös gondozás'];
        $extra_headers = ['látogatási szám', 'ellátottak száma', 'fürdetés', 'ebédes lát.', 'egyéb lát.', 'köz.gond.lát'];

        // split the table to the blocks
        $client_block = array_splice($table_data, 0, $client_count + 2);
        $service_block = array_splice($table_data, 0, count($service_headers) + 2);
        $extra_block = array_splice($table_data, 0);

        // split the headers to the blocks
        $client_header = array_splice($row_headers, 0, $client_count + 2);
        $service_header = array_splice($row_headers, 0, count($service_headers) + 2);
        $extra_header = array_splice($row_headers, 0);

        // prepare the blocks
        $this->hhPrepareBlock($service_block, $service_header, $service_headers, $day_count);
        $this->hhPrepareBlock($extra_block, $extra_header, $extra_headers, $day_count);

        // calculate the summary fields and clean the whole block
        $this->hhSums($service_block, $service_header, $day_count);
        $this->hhAddSeparatorRow($service_block, $service_header);

        $this->hhSums($extra_block, $extra_header, $day_count);
        $this->hhAddSeparatorRow($extra_block, $extra_header);

        // done building the rows, lets insert in the table
        $table_data = array_merge($client_block, $service_block, $extra_block);
        $row_headers = array_merge($client_header, $service_header, $extra_header);

        $hh_month->setData($table_data);
        $hh_month->setRowheaders($row_headers);

        return $hh_month;
    }

    /**
     * Prepare the block for hhSums (remove sum rows or fill empty rows)
     *
     * @param $block
     * @param $header
     * @param $headers
     * @param $day_count
     */
    private function hhPrepareBlock(&$block, &$header, $headers, $day_count)
    {
        if (!empty($block)) {
            // remove the sum and the separator rows from the block
            array_splice($block, -2);
            array_splice($header, -2);
        } else {
            foreach ($headers as $h) {
                $block[] = [''] + array_fill(1, $day_count + 2, '');
            }
            $header = $headers;
        }
    }

    /**
     * Add Client action
     * Only displays a list of the clients, and returns the changes to the Homehelp form (via js)
     * The actual db operations are in homehelpAction()
     *
     * @param Request $request
     * @param $social_worker
     * @param $month
     * @return array|Response
     *
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/home_help_addclient/{social_worker}/{month}", name="admin_addclient")
     * @Template("JCSGYKAdminBundle:Dialog:homehelp_addclient.html.twig")
     */
    public function addclientAction(Request $request, $social_worker = null, $month = null)
    {
        $ds = $this->container->get('jcs.ds');
        $em = $this->container->get('doctrine')->getManager();
        $hh_repo = $em->getRepository('JCSGYKAdminBundle:HomeHelp');

        $month = (new \DateTime($month))->setTime(0, 0, 0);

        // if no sw provided, exit with an exception
        if (empty($social_worker)) {
            throw new HttpException(400, "Bad request");
        }
        // get the clients of this social worker
        $my_clients = $hh_repo->getClientsBySocialWorker($social_worker, $ds->getCompanyId(), true, true);
        // the the inactive clients
        $my_inactive_clients = $hh_repo->getClientsBySocialWorker($social_worker, $ds->getCompanyId(), false, true);

        // find the clients associated with this homehelp_month record
        $hh_month = $this->getHHMonth($social_worker, $month);
        if (empty($hh_month)) {
            $set_clients = $my_clients;
        } else {
            $hh_clients = $hh_month->getClients();
            $set_clients = [];
            foreach ($hh_clients as $hh_client) {
                $set_clients[] = $hh_client->getId();
            }
        }

        $form = $this->addClientsForm($social_worker, $month, $my_clients, $set_clients);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // client list submitted in the form
            $new_client_list = $form->get('clients')->getData();

            // check added clients
            $clients_to_add = [];
            foreach ($new_client_list as $client_id) {
                if (!in_array($client_id, $set_clients)) {
                    $clients_to_add[] = $client_id;
                }
            }

            // check removed clients
            $clients_to_remove = [];
            foreach ($set_clients as $client_id) {
                if (!in_array($client_id, $new_client_list)
                    && !in_array($client_id, $my_clients)  // own clients can not be removed
                    && !in_array($client_id, $my_inactive_clients)  // also inactive clients may wont get displayed, but still should not be removed
                ) {
                    $clients_to_remove[] = $client_id;
                }
            }

            return [
                'success'   => true,
                'to_add'    => json_encode($clients_to_add),
                'to_remove' => json_encode($clients_to_remove),
            ];
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * Form of the addclient dialog
     *
     * @param $social_worker
     * @param \DateTime $month
     * @param $my_clients
     * @param $set_clients
     * @return \Symfony\Component\Form\Form
     */
    private function addClientsForm($social_worker, \DateTime $month, $my_clients, $set_clients)
    {
        $ds = $this->container->get('jcs.ds');
        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        // get all active home help clients
        $clients = $em->getRepository('JCSGYKAdminBundle:HomeHelp')->getActiveClients($ds->getCompanyId());
        $client_list = [];
        foreach ($clients as $client) {
            $client_list[$client->getId()] = $ae->formatClientName($client);
        }

        $defaults = [
            'my_clients' => json_encode($my_clients),
            'clients' => $set_clients,
        ];

        // build the form
        $form_builder = $this->createFormBuilder($defaults)
            ->setAction($this->generateUrl('admin_addclient', [
                'social_worker' => $social_worker,
                'month'         => $month->format('Y-m')
            ]))
            ->setMethod('POST')
            ->add('filter', 'text', [
                'label'    => 'Szűrő',
                'required' => false,
            ])
            ->add('clients', 'choice', [
                'label'    => 'Ügyfelek',
                'choices'  => $client_list,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('my_clients', 'hidden');

        return $form_builder->getForm();
    }

}
