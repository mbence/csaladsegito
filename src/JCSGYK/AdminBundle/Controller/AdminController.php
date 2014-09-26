<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Cocur\BackgroundProcess\BackgroundProcess;

use JCSGYK\AdminBundle\Entity\User;
use JCSGYK\AdminBundle\Entity\Parameter;
use JCSGYK\AdminBundle\Form\Type\UserType;
use JCSGYK\AdminBundle\Entity\Template;
use JCSGYK\AdminBundle\Form\Type\TemplateType;
use JCSGYK\AdminBundle\Entity\Utilityprovider;
use JCSGYK\AdminBundle\Form\Type\UtilityproviderType;
use JCSGYK\AdminBundle\Entity\Company;
use JCSGYK\AdminBundle\Form\Type\CompanyType;
use JCSGYK\AdminBundle\Entity\Club;
use JCSGYK\AdminBundle\Form\Type\ClubType;
use JCSGYK\AdminBundle\Entity\Paramgroup;
use JCSGYK\AdminBundle\Entity\Option;
use JCSGYK\AdminBundle\Form\Type\OptionType;
use JCSGYK\AdminBundle\Entity\MonthlyClosing;

class AdminController extends Controller
{
    /**
    * @Secure(roles="ROLE_ADMIN")
    */
    public function indexAction()
    {
//        $co = $this->container->get('jcs.ds')->getCompany();

//        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
//            $this->get('logger')->info('ROLE_ADMIN');
//        }

        return $this->render('JCSGYKAdminBundle:Admin:index.html.twig', []);
    }

    /**
     * Manages the users from the admin_user table
     *
     * Create new user with /admin/users/new
     * Edit user with /admin/users/:id
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function usersAction($id)
    {
        $request = $this->getRequest();

        $user = null;
        $um = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');
        $company_id = $ds->getCompanyId();

        $session = $this->get('session');
        $filter_data = $session->get('userfilter', ['enabled' => true]);

        // create the user filter
        $filter = $this->getFilterForm($filter_data);

        // save the filter change
        if ($request->isMethod('POST')) {
            $filter->bind($request);
            $filter_data = $filter->getData();
            if (!empty($filter_data['filter'])) {
                if ($filter->get('clear')->isClicked()) {
                    $filter_data = ['enabled' => true];
                }
                // save the filter and redirect back
                $session->set('userfilter', $filter_data);

                return $this->redirect($this->generateUrl('admin_users', ['id' => $id]));
            }
        }

        // get filtered users
        $users = $this->getUsersFiltered($filter_data, $company_id);

        if ('new' == $id) {
            // new user
            $user = $um->createUser();
            $user->setCompanyId($company_id);
        }
        elseif (!is_null($id)) {
            $user = $em->getRepository('JCSGYKAdminBundle:User')
                ->findOneBy(['id' => $id, 'companyId' => $company_id]);
        }
        if (!empty($user)) {
            $sec = $this->get('security.context');
            $form = $this->createForm(new UserType($this->container->get('jcs.ds'), $sec), $user);
            // only superadmins can see and edit superadmins
            if (!$sec->isGranted('ROLE_SUPER_ADMIN') && in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                throw new HttpException(401, "Unauthorized access");
            }

            // save the user
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    // save the new user data
                    if ('new' == $id) {
                        $em->persist($user);
                    }
                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'felhasználó elmentve');

                    return $this->redirect($this->generateUrl('admin_users', ['id' => $user->getId()]));
                }
            }

            $form_view = $form->createView();
        }
        else {
            $form_view = null;
        }

        return $this->render('JCSGYKAdminBundle:Admin:users.html.twig', [
            'users' => $users,
            'form'  => $form_view,
            'user'  => $user,
            'id'    => $id,
            'filter' => $filter->createView(),
            'logs'  => !empty($user) ? $this->container->get('history.logger')->getLogs($user) : [],
        ]);
    }

    private function getFilterForm($filter_data)
    {
        $ds = $this->container->get('jcs.ds');

        // create the user filter
        return $this->createFormBuilder($filter_data)
            ->add('quicksearch', 'text')
            ->add('role', 'choice', [
                'label' => ' ',
                'choices' => $ds->getRoles(),
                'required' => false
            ])
            ->add('enabled', 'checkbox', [
                'label' => 'aktív'
            ])
            ->add('clear', 'submit', [
                'label' => 'x',
                'attr' => [
                    'title' => 'filter törlése'
                ]
            ])
            ->add('filter', 'hidden', [
                'data'  => 1
            ])
            ->getForm();
    }

    /**
     * Get the users list with the filters applied
     * @param array $filter_data
     * @param int $company_id
     * @return array of User records
     */
    private function getUsersFiltered($filter_data, $company_id)
    {
        $em = $this->getDoctrine()->getManager();
        // only superadmins can see and edit superadmins
        $sql = 'SELECT u FROM JCSGYKAdminBundle:User u WHERE u.companyId=:company ';
        if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $sql .= " AND u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'";
        }
        // add filters
        $sql .= ' AND u.enabled = :enabled';
        if  (!empty($filter_data['quicksearch'])) {
            $sql .= " AND (CONCAT(u.lastname, ' ', u.firstname) LIKE :qs OR u.username LIKE :qs)";
        }
        if  (!empty($filter_data['role'])) {
            $sql .= " AND u.roles LIKE :role";
        }

        $sql .= ' ORDER BY u.lastname, u.firstname';

        $q = $em->createQuery($sql)
            ->SetParameter('company', $company_id)
            ->SetParameter('enabled', $filter_data['enabled']);
        if  (!empty($filter_data['quicksearch'])) {
            $q->SetParameter('qs', "%{$filter_data['quicksearch']}%");
        }
        if  (!empty($filter_data['role'])) {
            $q->SetParameter('role', "%{$filter_data['role']}%");
        }

        return $q->getResult();
    }

    /**
     * Lists the paramgroups
     *
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function paramgroupsAction($type)
    {
        $request = $this->getRequest();

        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');
        $co = $ds->getCompanyId();
        $client_type_names = $ds->getClientTypeNames(true);

        // save the current param group
        if ($request->isMethod('POST')) {
            $paramsave = $request->request->get('parameter');

            if (!empty($paramsave)) {
                foreach ($paramsave as $param_id => $param) {
                    if (!empty($param['id'])) {
                        // update a parameter
                        $orig = $em->getRepository('JCSGYKAdminBundle:Paramgroup')->getOne($param['id'], $co);
                        if (empty($orig)) {
                            // original parameter not found, exit
                            throw new HttpException(400, "Bad request");
                        }

                        $orig->setPosition($param['position']);
                        $orig->setName($param['name']);
                        $orig->setIsActive(isset($param['isActive']));
                        $orig->setRequired(isset($param['required']));
                        $orig->setControl((isset($param['control']) ? 1 : 0));
                    }
                    else {
                        // insert new param group
                        if (!empty($param['name'])) {
                            $new_param = new Paramgroup;
                            if ($param['type'] == 0) {
                                $new_param->setCompanyId(0);
                            }
                            else {
                                $new_param->setCompanyId($co);
                            }
                            $new_param->setPosition($param['position']);
                            $new_param->setType($param['type']);
                            $new_param->setClientType($param['clientType']);
                            $new_param->setName($param['name']);
                            $new_param->setIsActive(isset($param['isActive']));
                            $new_param->setRequired(isset($param['required']));
                            $new_param->setControl((isset($param['control']) ? 1 : 0));

                            $em->persist($new_param);
                        }
                    }
                }
                $em->flush();
                $act_grp = $request->request->get('group', 0);
                $this->get('session')->getFlashBag()->add('notice', 'paramétercsoport elmentve');
            }

            $group_id = $request->request->get('clientType', 0) . '-' . $request->request->get('group', 0);

            return $this->redirect($this->generateUrl('admin_paramgroups', ['type' => $group_id]));
        }

        // get all paramgroup types
        $types = $ds->getGroupTypes(false);
        $groups = $ds->getParamgroups();

        return $this->render('JCSGYKAdminBundle:Admin:paramgroups.html.twig', [
            'groups' => $groups,
            'client_types' => $client_type_names,
            'types' => $types,
            'act' => $type
        ]);
    }

    /**
     * Lists the params from the parameters table
     *
     * @Secure(roles="ROLE_ADMIN")
     *
     * @param mixed $group selected paramgroup id
     * @param bool $sys system or normal parameters?
     */
    public function paramsAction($group, $sys = false)
    {
        $request = $this->getRequest();
        $route = $sys ? 'admin_systemparams' : 'admin_params';

        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');
        $co = $ds->getCompanyId();


        // save the current param group
        if ($request->isMethod('POST')) {
            $paramsave = $request->request->get('parameter');

            foreach ($paramsave as $param_id => $param) {
                if (!empty($param['id'])) {
                    // update a parameter
                    $orig = $em->getRepository('JCSGYKAdminBundle:Parameter')->getOne($param['id'], $co);
                    if (empty($orig)) {
                        // original parameter not found, exit
                        throw new HttpException(400, "Bad request");
                    }

                    $orig->setPosition($param['position']);
                    $orig->setName($param['name']);
                    $orig->setIsActive(isset($param['isActive']));
                }
                else {
                    // insert new param
                    if (!empty($param['name'])) {
                        $new_param = new \JCSGYK\AdminBundle\Entity\Parameter;
                        $new_param->setCompanyId($co);
                        $new_param->setPosition($param['position']);
                        $new_param->setGroup($param['group']);
                        $new_param->setName($param['name']);
                        $new_param->setIsActive(isset($param['isActive']));

                        $em->persist($new_param);
                    }
                }
            }
            $em->flush();
            $act_grp = $request->request->get('group', 1);
            $this->get('session')->getFlashBag()->add('notice', 'paraméterek elmentve');

            return $this->redirect($this->generateUrl($route, ['group' => $request->request->get('group', 1)]));
        }

        $all_groups = [];

        if ($sys) {
            $system_groups = $this->container->getParameter('system_parameter_groups');
            foreach ($system_groups as $k => $sg) {
                $all_groups[] = [
                    'id' => $k,
                    'name' => $sg[0],
                    'type' => 0,
                    'clientType' => 0,
                    'required' => $sg[1],
                    'control' => $sg[2]
                ];
            }
        }
        else {
            $all_groups = $ds->getParamGroup();
        }

        return $this->render('JCSGYKAdminBundle:Admin:params.html.twig', [
            'sys' => $sys,
            'params' => $ds->getAll(),
            'groups' => $all_groups,
            'act' => $group,
            'types' => $ds->getGroupTypes($sys),
            'route' => $route,
            'client_types' => $ds->getClientTypeNames(true),
        ]);
    }

    /**
     * Edit the company parameters
     *
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function companiesAction($id = null)
    {
        $request = $this->getRequest();
        $company = null;
        $form_view = null;

        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');

        // prepare the client types data array
        $client_types = [];
        $client_type_names = $ds->getClientTypeNames();
        $client_type_slugs = $ds->getAllClientTypes();
        foreach ($client_type_names as $ct_id => $ct_name) {
            $client_types[] = [
                'id' => $ct_id,
                'label' => $ct_name,
                'slug' => $client_type_slugs[$ct_id],
            ];
        }

        if ('new' == $id) {
            // new company
            $company = new Company;
        }
        elseif (!is_null($id)) {
            $company = $em->getRepository('JCSGYKAdminBundle:Company')->find($id);
        }

        if (is_null($id) || !empty($company)) {

            if (!empty($company)) {
                $form = $this->createForm(new CompanyType($ds, $company), $company);
                $original_policy = $company->getSequencePolicy();
            }

            // save the current company
            if ($request->isMethod('POST')) {

                $form->bind($request);

                if ($form->isValid()) {

                    if (is_null($company->getId())) {
                        $em->persist($company);
                    }

                    // save sequence policy and case number template
                    $client_types = array_flip($ds->getAllClientTypes());
                    $sp_data = [];
                    $template_data = [];
                    foreach ($client_types as $type) {
                        $sp_data[$type] = $form->get('sequence_policy_'.$type)->getData();
                        $template_data[$type] = $form->get('case_number_template_'.$type)->getData();
                    }
                    $company->setSequencePolicy($sp_data);
                    $company->setCaseNumberTemplate($template_data);

                    $em->flush();

                    foreach ($client_types as $type) {
                        if ('new' == $id) {
                            // reset - create the sequence for this new company
                            $this->get('jcs.seq')->reset(['id' => $company->getId(), $type, 'sequencePolicy' => $company->getSequencePolicy()]);
                        }
                        else {
                            // update the sequence for the policy change
                            if ($original_policy[$type] != $company->getSequencePolicy()[$type]) {
                                $year = $company->getSequencePolicy()[$type] == Company::BY_YEAR ? date('Y') : null;
                                $this->get('jcs.seq')->setYear(['id' => $company->getId(), 'sequencePolicy' => $company->getSequencePolicy()], $type, $year);
                            }
                        }
                    }

                    $this->get('session')->getFlashBag()->add('notice', 'Cég elmentve');

                    return $this->redirect($this->generateUrl('admin_companies', ['id' => $company->getId()]));
                }
            }

            if (!empty($form)) {
                $form_view = $form->createView();
            }
            // get all companies
            $companies = $em->getRepository('JCSGYKAdminBundle:Company')->findBy([], ['name' => 'ASC']);

            return $this->render('JCSGYKAdminBundle:Admin:companies.html.twig', [
                'companies' => $companies,
                'id' => $id,
                'act' => $company,
                'form' => $form_view,
                'client_types' => $client_types,
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Edit the clubs parameters
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function clubsAction($id = null)
    {
        $request = $this->getRequest();
        $club = null;
        $form_view = null;

        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');
        // get the current company id from the datatore
        $company_id = $ds->getCompanyId();

        if ('new' == $id) {
            // new club
            $club = new Club;
        }
        elseif (!is_null($id)) {
            $club = $em->getRepository('JCSGYKAdminBundle:Club')->findBy(['id' => $id, 'companyId' => $company_id]);
            if (!empty($club[0])) {
                $club = $club[0];
            }
        }

        if (is_null($id) || !empty($club)) {

            if (!empty($club)) {
                $form = $this->createForm(new ClubType($ds), $club);
            }

            // save the current club
            if ($request->isMethod('POST')) {

                $form->bind($request);

                if ($form->isValid()) {

                    if (is_null($club->getId())) {
                        // save the company id too
                        $club->setCompanyId($company_id);
                        $em->persist($club);
                    }

                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Klub elmentve');

                    return $this->redirect($this->generateUrl('admin_clubs', ['id' => $club->getId()]));
                }
            }

            if (!empty($form)) {
                $form_view = $form->createView();
            }
            // get all clubs
            $clubs = $em->getRepository('JCSGYKAdminBundle:Club')->findBy(['companyId' => $company_id], ['name' => 'ASC']);

            return $this->render('JCSGYKAdminBundle:Admin:clubs.html.twig', [
                'clubs' => $clubs,
                'id' => $id,
                'act' => $club,
                'form' => $form_view,
                'logs' => !empty($club) ? $this->container->get('history.logger')->getLogs($club) : [],
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    public function closingsAction($id = null)
    {
        $request = $this->getRequest();
        $closing = null;
        $form_view = null;
        $auto_refresh = false;

        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');
        // get the current company id from the datatore
        $company_id = $ds->getCompanyId();
        $user = $ds->getUser();

        if (!is_null($id)) {
            $closing = $em->getRepository('JCSGYKAdminBundle:MonthlyClosing')->findBy(['id' => $id, 'companyId' => $company_id]);
            if (!empty($closing[0])) {
                $closing = $closing[0];
            }

            if (!empty($closing->getFiles()) && $request->query->get('download')) {
                // send the zip file to download
                $fn = 'etkeztetes_import_' . $closing->getCreatedAt()->format('Ymd') . '.zip';

                return $this->sendDownloadResponse($fn, stream_get_contents($closing->getFiles()), 'application/zip');
            }
        }

        // check the background process
        $process = $this->get('session')->get('monthly_closing_process');
        if (!empty($process)) {
            if ($process->isRunning()) {
                $auto_refresh = true;

            }
            else {
                $this->get('session')->remove('monthly_closing_process');
            }
        }

        $form = $this->createFormBuilder()
            ->add('period', 'choice', [
                'choices' => ['napi zárás', 'havi zárás'],
                'data' => 0,
            ])
            ->getForm();

        $closing_service = $this->container->get('jcs.closing');

        // manual run
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid() && 'manual' == $request->request->get('run')) {

                $data = $form->getData();

                // create a process for the background running
                $kernel = $this->container->get('kernel');
                $php = $this->container->getParameter('php_path', '/usr/bin/php');
                $console = sprintf('%s %s/console', $php, $kernel->getRootDir());
                $command = sprintf('jcs:closing %s --user=%s --env=%s --no-debug', $company_id, $user->getId(), $kernel->getEnvironment());
                if (0 == $data['period']) {
                    $command .= ' -a';
                }

                $process = new BackgroundProcess($console . ' ' . $command);
                $process->run();

                // save the process in session
                $this->get('session')->set('monthly_closing_process', $process);

                //$closing = $closing_service->run($data['period']);

                if (!empty($closing)) {
                    $id = $closing->getId();
                }

                $this->get('session')->getFlashBag()->add('notice', (0 == $data['period'] ? 'Napi' : 'Havi') . ' zárás elindítva');

                return $this->redirect($this->generateUrl('admin_closings', ['id' => $id]));
            }
        }

        return $this->render('JCSGYKAdminBundle:Admin:closings.html.twig', [
            'closings' => $closing_service->getList(),
            'act' => $closing,
            'form' => $form->createView(),
            'auto_refresh' => $auto_refresh,
        ]);
    }

    public function dailyordersAction($id = null)
    {
        $request = $this->getRequest();
        $order = null;
        $form_view = null;
        $auto_refresh = false;

        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');
        $dailyorders_service = $this->container->get('jcs.orders');

        // get the current company id from the datatore
        $company_id = $ds->getCompanyId();
        $user = $ds->getUser();

        if (!is_null($id)) {
            $order = $em->getRepository('JCSGYKAdminBundle:DailyOrder')->findBy(['id' => $id, 'companyId' => $company_id]);
            if (!empty($order[0])) {
                $order = $order[0];
            }

            if (!empty($order->getFile()) && $request->query->get('download')) {
                // send the zip file to download
                $attachment = $dailyorders_service->getAttachmentName($order);

                return $this->sendDownloadResponse($attachment, stream_get_contents($order->getFile()), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            }
        }

        // check the background process
        $process = $this->get('session')->get('dailyorders_process');
        if (!empty($process)) {
            if ($process->isRunning()) {
                $auto_refresh = true;

            }
            else {
                $this->get('session')->remove('dailyorders_process');
            }
        }

        $form = $this->createFormBuilder()->getForm();

        // manual run
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid() && 'manual' == $request->request->get('run')) {

                // create a process for the background running
                $kernel = $this->container->get('kernel');
                $php = $this->container->getParameter('php_path', '/usr/bin/php');

                $console = sprintf('%s %s/console', $php, $kernel->getRootDir());
                $command = sprintf('jcs:orders %s --user=%s --env=%s --no-debug' , $company_id, $user->getId(), $kernel->getEnvironment());

                $process = new BackgroundProcess($console . ' ' . $command);
                $process->run();

                // save the process in session
                $this->get('session')->set('dailyorders_process', $process);

//                $order = $dailyorders_service->run();

                if (!empty($order)) {
                    $id = $order->getId();
                }

                $this->get('session')->getFlashBag()->add('notice', 'Napi megrendelés elindítva');

                return $this->redirect($this->generateUrl('admin_dailyorders', ['id' => $id]));
            }
        }

        return $this->render('JCSGYKAdminBundle:Admin:dailyorders.html.twig', [
            'orders' => $dailyorders_service->getList(),
            'act' => $order,
            'form' => $form->createView(),
            'auto_refresh' => $auto_refresh,
        ]);
    }

    /**
     * System update action
     *
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function updateAction()
    {
        $request = $this->getRequest();

        $ex = [];
        $re = [];
        $session = $this->get('session');

        if ($request->isMethod('POST')) {

            $php = $this->container->getParameter('php_path', '/usr/bin/php');
            $app_root = $this->get('kernel')->getRootDir() . '/..';

            if ($request->get('update')) {
                $kernel = $this->container->get('kernel');
                $console = sprintf('%s %s/console ', $php, $kernel->getRootDir());

                // git pull
                $ex[] = 'git pull https://github.com/mbence/csaladsegito.git master';
                if (!empty($request->get('migrate'))) {
                    // migrations
                    $ex[] = $console . 'doctrine:migrations:migrate --no-interaction';
                }
                // clear cache
                $ex[] = $console . 'cache:clear --env=prod --no-debug';
                // assetic dump
                //$ex[] = $console . 'assetic:dump --env=prod --no-debug';
                // asset install
                //$ex[] = $console . 'assets:install';
            }
            /*
            elseif ($request->get('composer')) {
                putenv("COMPOSER_HOME={$app_root}");
                // run composer selfupdate and update
                $ex[] = $php . ' composer.phar selfupdate';
                // run composer selfupdate and update
                $ex[] = $php . ' composer.phar update';
            }
             */

            if (!empty($ex)) {
                // switch to symfony root dir
                chdir($app_root);

                foreach ($ex as $com) {
                    $output = '';
                    $return_val = '';
                    exec($com . ' 2>&1', $output, $return_val);
                    $re [] = ['ex' => $com, 'return_val' => $return_val, 'output' => implode($output, '<br>')];
                }

                $session->set('update', $re);
            }

            return $this->redirect($this->generateUrl('admin_update'));
        }

        if ($session->has('update')) {
            $re = $session->get('update');
            $session->remove('update');
        }

        return $this->render('JCSGYKAdminBundle:Admin:update.html.twig', ['result' => $re]);
    }


    /**
     * Edit and upload document templates
     */
    public function templatesAction($id = null)
    {
        $template = null;
        $form_view = null;

        $request = $this->getRequest();

        $em = $this->getDoctrine()->getManager();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        if ('new' == $id) {
            // new template
            $template = new Template;
            $template->setCompanyId($company_id);
        }
        elseif (!is_null($id)) {
            $template = $em->getRepository('JCSGYKAdminBundle:Template')
                ->findOneBy(['id' => $id, 'companyId' => $company_id]);
        }

        if (is_null($id) || !empty($template)) {

            if (!empty($template)) {
                $form = $this->createForm(new TemplateType(), $template);
            }

            // save the template
            if ($request->isMethod('POST')) {

                $form->bind($request);

                if ($form->isValid()) {

                    $template->setModifiedAt(new \DateTime());

                    if (is_null($template->getId())) {
                        $em->persist($template);
                    }

                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Nyomtatvány elmentve');

                    return $this->redirect($this->generateUrl('admin_templates', ['id' => $template->getId()]));
                }
            }

            if (!empty($form)) {
                $form_view = $form->createView();
            }
            // get all templates
            $templates = $em->getRepository('JCSGYKAdminBundle:Template')->findBy(['companyId' => $company_id], ['name' => 'ASC']);

            return $this->render('JCSGYKAdminBundle:Admin:templates.html.twig', ['templates' => $templates, 'id' => $id, 'act' => $template, 'form' => $form_view]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Download document templates
     */
    public function templatesDownloadAction($id = null)
    {
        if (!empty($id)) {
            $em = $this->getDoctrine()->getManager();
            $template = $em->getRepository('JCSGYKAdminBundle:Template')->find($id);
        }

        if (!empty($template)) {

            $docpath = $template->getAbsolutePath();

            if (!file_exists($docpath)) {
                throw new HttpException(400, "Bad request");
            }

            return $this->sendDownloadResponse($template->getOriginalName(), file_get_contents($docpath), $template->getMimeType());
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    public function sendDownloadResponse($file_name, $file_contents, $content_type)
    {
        $response = new Response();

        $response->headers->set('Content-Type', $content_type);
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $file_name . '"');

        $response->setContent($file_contents);

        return $response;
    }

    /**
     * Utilityproviders
     *
     * @param int $id
     */
    public function providersAction($id = null)
    {
        $provider = null;
        $form_view = null;

        $request = $this->getRequest();

        $em = $this->getDoctrine()->getManager();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        if ('new' == $id) {
            // new template
            $provider = new Utilityprovider;
            $provider->setCompanyId($company_id);
            $provider->setIsActive(true);
        }
        elseif (!is_null($id)) {
            $provider = $em->getRepository('JCSGYKAdminBundle:Utilityprovider')
                ->findOneBy(['id' => $id, 'companyId' => $company_id]);
        }

        if (is_null($id) || !empty($provider)) {

            if (!empty($provider)) {
                $form = $this->createForm(new UtilityproviderType(), $provider);
            }

            // save the template
            if ($request->isMethod('POST')) {

                $form->bind($request);

                if ($form->isValid()) {

                    if (is_null($provider->getId())) {
                        $em->persist($provider);
                    }

                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Szolgáltató elmentve');

                    return $this->redirect($this->generateUrl('admin_providers', ['id' => $provider->getId()]));
                }
            }

            if (!empty($form)) {
                $form_view = $form->createView();
            }
            // get all templates
            $providers = $em->getRepository('JCSGYKAdminBundle:Utilityprovider')->findBy(['companyId' => $company_id], ['name' => 'ASC']);

            return $this->render('JCSGYKAdminBundle:Admin:providers.html.twig', ['providers' => $providers, 'id' => $id, 'act' => $provider, 'form' => $form_view]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Options table
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function optionsAction($name, $id = null)
    {
        $option_types = ['cateringcosts', 'holidays', 'homehelpcosts'];
        $options_default_value = [
            'cateringcosts' => [
                'valid_from' => '',
                'data' => [[null,null,null,null]],
                'format' => [
                    'colWidths' => [100,100,100,100],
                    'colHeaders' => ['-tól', '-ig', 'díj', 'egyedülálló'],
                    'columns' => [
                        [
                            'type'     => 'numeric',
                            'format'   => '0 0[,]00 $',
                            'language' => 'hu'
                        ],
                        [
                            'type'     => 'numeric',
                            'format'   => '0 0[,]00 $',
                            'language' => 'hu'
                        ],
                        [
                            'type'     => 'numeric',
                            'format'   => '0 0[,]00 $',
                            'language' => 'hu'
                        ],
                        [
                            'type'     => 'checkbox'
                        ]
                    ]
                ]
            ],
            'homehelpcosts' => [
                'valid_from' => '',
                'data' => [[null,null,null,null]],
                'format' => [
                    'colWidths' => [100,100,100,100],
                    'colHeaders' => ['-tól', '-ig', 'díj', 'egyedülálló'],
                    'columns' => [
                        [
                            'type'     => 'numeric',
                            'format'   => '0 0[,]00 $',
                            'language' => 'hu'
                        ],
                        [
                            'type'     => 'numeric',
                            'format'   => '0 0[,]00 $',
                            'language' => 'hu'
                        ],
                        [
                            'type'     => 'numeric',
                            'format'   => '0 0[,]00 $',
                            'language' => 'hu'
                        ],
//                        [
//                            'type'     => 'checkbox'
//                        ]
                    ]
                ]
            ],
            'holidays' => [
                'valid_from' => date('Y-01-01'),
                'data' => [[null,null,null]],
                'format' => [
                    'colWidths' => [95, 105, 219],
                    'colHeaders' => ['Dátum', 'Típus', 'Megnevezés'],
                    'columns' => [
                        [
                            'type'     => 'date',
                            'format'   => 'yy-mm-dd'
                        ],
                        [
                            'type'     => 'dropdown',
                            'source'   => array_values($this->container->get('jcs.ds')->getHolidayTypeMap())
                        ],
                        [
                            'type'     => 'text'
                        ]
                    ]
                ]
            ]
        ];

        if (empty($name) || !in_array($name, $option_types)) {
            throw new HttpException(400, "Bad request");
            exit();
        }

        $request   = $this->getRequest();
        $option    = null;
        $form_view = null;
        $em        = $this->getDoctrine()->getManager();
        $sec       = $this->get('security.context');
        $user      = $sec->getToken()->getUser();

        if ('new' == $id) {
            // new option
            $option = new Option;
            $option->setName($name);
            //
            // ez így nem jó, de nem tudom hogyan kellene átadni NEW esetén
            //
            $option->setValue(json_encode($options_default_value[$name]['data']));
            $option->setValidFrom(new \DateTime($options_default_value[$name]['valid_from']));
            $option->setIsActive(true);
        }
        elseif (!is_null($id)) {
            $option = $em->getRepository('JCSGYKAdminBundle:Option')->find($id);
            $this->prepareValue($option);
        }

        if (is_null($id) || (!empty($option) && $name == $option->getName())) {

            if (!empty($option)) {
                $form = $this->createForm(new OptionType(), $option);
            }

            // save the current option
            if ($request->isMethod('POST')) {

                $form->bind($request);
                $error = $this->processValue($option);

                if (! empty($error)) {
                    $form->get('value')->addError(new \Symfony\Component\Form\FormError($error));
                }

                if ($form->isValid()) {
                    // set modifier user
                    $option->setModifier($user);
                    // set modified at
                    $option->setModifiedAt(new \DateTime());

                    if (is_null($option->getId())) {
                        // set the creator
                        $option->setCreator($user);
                        // get the current company id from the datatore
                        $company_id = $this->container->get('jcs.ds')->getCompanyId();
                        // save the company id too
                        $option->setCompanyId($company_id);
                        $em->persist($option);
                    }

                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Opciók elmentve');

                    return $this->redirect($this->generateUrl('admin_options', ['name' => $option->getName(), 'id' => $option->getId()]));
                }
            }

            if (!empty($form)) {
                $form_view = $form->createView();
            }
            // get all options named with $name
            $options = $em->getRepository('JCSGYKAdminBundle:Option')->findBy(['name' => $name], ['validFrom' => 'DESC']);

            return $this->render('JCSGYKAdminBundle:Admin:options.html.twig', [
                    'name'           => $name,
                    'id'             => $id,
                    'options'        => $options,
                    'form'           => $form_view,
                    'table_defaults' => json_encode($options_default_value[$name]['format'])
                ]
            );
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Prepare value by holiday type map
     *
     * @param Option $option
     */
    private function prepareValue(Option &$option)
    {
        if ($option->getName() == 'holidays') {
            $holiday_types = $this->container->get('jcs.ds')->getHolidayTypeMap();
            $value = json_decode($option->getValue());

            foreach ($value as $row) {
                $new_row     = $row;
                $new_row[1]  = $holiday_types[$row[1]];
                $new_value[] = $new_row;
            }
            $option->setValue(json_encode($new_value));
        }
    }

    /**
     * Process option value, trim white spacec and empty lines
     * If option name is holidays, does a holiday name mapping
     *
     * @param Option $option
     */
    private function processValue(Option &$option)
    {
        if ($option->getName() == 'cateringcosts' || $option->getName() == 'holidays') {
            $value = json_decode($option->getValue());

            if ($value != null && is_array($value)) {
                $empty_rows = [];

                foreach ($value as $key => $row) {
                    if (empty(trim(implode('', $row)))) {
                        $empty_rows[] = $key;
                    }
                }
                foreach ($empty_rows as $key) {
                    unset($value[$key]);
                }

                if (empty($value)) {
                    return 'Üres táblázat!';
                }
                else {
                    $option->setValue(json_encode(array_values($value)));
                }
            }
        }
        if ($option->getName() == 'holidays') {
            $value         = json_decode($option->getValue());
            $holiday_types = array_flip($this->container->get('jcs.ds')->getHolidayTypeMap());
            $error         = false;

            foreach ($value as $row) {
                $new_row     = $row;
                if (empty($row[1]) || ! isset($holiday_types[$row[1]])) {
                    $error = true;
                    break;
                }
                $new_row[1]  = $holiday_types[$row[1]];
                $new_value[] = $new_row;
            }

            if ($error) {
                return 'Ismeretlen munkaszüneti nap típus!';
            } else {
                $option->setValue(json_encode($new_value));
            }
        }
    }

    public function recommendedFieldsAction($tab = 0)
    {
        $request = $this->getRequest();

        $em = $this->getDoctrine()->getManager();
        $ds = $this->container->get('jcs.ds');
        $co = $ds->getCompanyId();

        $client_types = $ds->getClientTypeNames(true);
        if (empty($type)) {
            reset($client_types);
            $type = key($client_types);
        }

        $fields = [
            'birth_place'            => 'Születési hely',
            'birth_date'             => 'Születési idő',
            'mother_lastname'        => 'Anyja vezetékneve',
            'mother_firstname'       => 'Anyja keresztneve',
            'city'                   => 'Város',
            'street'                 => 'Utca',
            'street_type'            => 'Közterület jellege',
            'street_number'          => 'Házszám',
            'social_security_number' => 'Taj szám',
        ];

        // get recommended fields from the Options table
        $options = $em->createQuery("SELECT o FROM JCSGYKAdminBundle:Option o WHERE o.companyId = :company_id AND o.name = :name AND o.isActive = 1 ORDER BY o.validFrom DESC")
            ->setParameter('company_id', $co)
            ->setParameter('name', 'recommended_fields')
            ->setMaxResults(1)
            ->getResult();
        if (!empty($options[0])) {
            $option = $options[0];
            $ofields = json_decode($option->getValue(), true);
        }

        $form_builder = $this->createFormBuilder();
        foreach ($client_types as $ct => $ct_label) {
            $form_builder->add('fields_' . $ct, 'choice', [
                'choices'  => $fields,
                'multiple' => true,
                'expanded' => true,
                'data'     => isset($ofields[$ct]) ? $ofields[$ct] : [],
            ]);
        }
        $form_builder->add('act_tab', 'hidden', [
            'data' => $tab,
        ]);
        $form = $form_builder->getForm();

        // save the current param group
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $data = $form->getData();

                // build the options array
                $opt_save = [];
                foreach ($client_types as $ct => $ct_label) {
                    $opt_save[$ct] = $data['fields_' . $ct];
                }

                // save the option
                if (!isset($option)) {
                    $option = new Option();
                    $option->setName('recommended_fields');
                    $option->setCompanyId($co);
                    $option->setIsActive(true);
                    $option->setValidFrom(new \DateTime());
                    $em->persist($option);
                }
                $option->setValue(json_encode($opt_save));
                $em->flush();

                $this->get('session')->getFlashBag()->add('notice', 'Ajánlott mezők elmentve');

                return $this->redirect($this->generateUrl('admin_recommended_fields', ['tab' => $data['act_tab']]));
            }
        }

        return $this->render('JCSGYKAdminBundle:Admin:recommended_fields.html.twig', [
                    'act'          => $type,
                    'types'        => $ds->getGroupTypes(0),
                    'client_types' => $client_types,
                    'form'         => $form->createView(),
        ]);
    }

    /**
    * @Secure(roles="ROLE_ADMIN")
    */
    public function homehelpAction($social_worker = null, $month = null)
    {
        $ds = $this->container->get('jcs.ds');
        $sec = $this->container->get('security.context');
        $co = $ds->getCompany();
        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        $clients = [];
        // if no sw provided, select the first from the list
        if (empty($social_worker)) {
            $social_workers = $ds->getSocialWorkers();
            reset($social_workers);
            $social_worker = key($social_workers);
        }
        // find the clients of this social worker
        if (!empty($social_worker)) {
            $clients = $em->getRepository('JCSGYKAdminBundle:HomeHelp')->getClientsBySocialWorker($social_worker, $ds->getCompanyId());
        }

        if (empty($month)) {
            $month = 'today';
        }

        $month = new \DateTime($month);
        $day_count = $month->format('t');

        $table_data = [];
        $rowHeaders = [];
        // get the data from db for each client

        if (empty($table_data)) {
            // build the data if empty
            foreach ($clients as $client) {
                $tmp = array_fill(2, $day_count, '');
                $tmp[0] = $client->getId();

                $table_data[] = $tmp;
                $rowHeaders[] = $ae->formatName($client->getFirstname(), $client->getLastname(), $client->getTitle());
            }
        }
        // add summary row
        $tmp = ['sum'];
        foreach ($table_data as $row) {
            foreach ($row as $n => $cell) {
                if ($n > 0 && is_numeric($cell)) {
                    if (!empty($cell)) {
                        if (empty($tmp[$n])) {
                            $tmp[$n] = 0;
                        }
                        $tmp[$n] += $cell;
                    }
                }
            }
        }
        $table_data[] = $tmp;
        $rowHeaders[] = 'összesen';

        // add the additional rows below the client data
        $table_data[] = null;
        $rowHeaders[] = '';
        foreach (['közlekedés', 'vásárlás', 'ügyintézés', 'központ', 'közös gondozás'] as $task) {
            $tmp = array_fill(2, $day_count, '');
            $tmp[0] = '';
            $table_data[] = $tmp;
            $rowHeaders[] = $task;
        }

        // add summary row
        $tmp = ['sum'];
        for ($r = count($clients) + 2; $r < count($table_data); $r++) {
            foreach ($table_data[$r] as $n => $cell) {
                if ($n > 0 && is_numeric($cell)) {
                    if (!empty($cell)) {
                        if (empty($tmp[$n])) {
                            $tmp[$n] = 0;
                        }
                        $tmp[$n] += $cell;
                    }
                }
            }
        }
        $table_data[] = $tmp;
        $rowHeaders[] = 'összesen';

        $table_data[] = null;
        $rowHeaders[] = '';
        foreach (['látogatási szám', 'ellátottak száma', 'fürdetés', 'ebédes lát.', 'egyéb lát.', 'köz.gond.lát'] as $task) {
            $tmp = array_fill(2, $day_count, '');
            $tmp[0] = '';
            $table_data[] = $tmp;
            $rowHeaders[] = $task;
        }

        // add total counts
        foreach ($table_data as $r => $row) {
            if (is_array($row) && !empty($row)) {
                $sum = 0;

                foreach ($row as $n => $cell) {
                    if ($n > 0 && is_numeric($cell)) {
                        if (!empty($cell)) {
                            $sum += $cell;
                        }
                    }
                }
                $table_data[$r][$day_count + 1] = $sum;
            }
        }

//        var_dump($table_data);

        return $this->render('JCSGYKAdminBundle:Admin:homehelp.html.twig', [
            'form'           => $this->homeHelpForm($table_data)->createview(),
            'filter_form'    => $this->homeHelpFilter($social_worker, $month)->createView(),
            'clients'        => $clients,
            'table_defaults' => $this->getHomehelpDefaults($month, $rowHeaders),
            'hh_weekends'    => $this->getHomehelpWeekends($month),
        ]);
    }

    private function homeHelpFilter($social_worker, \DateTime $month)
    {
        $ds = $this->container->get('jcs.ds');
        $ae = $this->container->get('jcs.twig.adminextension');

        // build the filter form
        $form_builder = $this->createFormBuilder(['social_worker' => $social_worker, 'month' => $month->format('Y-m')]);

        // add the social workers
        $form_builder->add('social_worker', 'choice', [
            'label'   => 'Gondozó',
            'choices' => $ds->getSocialWorkers(),
        ]);

        // add the months for a year before
        $months = [];
        $m = new \DateTime('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $months[$m->format('Y-m')] = $ae->formatDate($m, 'ym');
            $m->modify('+1 month');
        }

        $form_builder->add('month', 'choice', [
            'label'   => 'Hónap',
            'choices' => $months,
        ]);

        // ok button
        $form_builder->add('filter_button', 'submit', [
            'label' => 'ok',
        ]);

        return $form_builder->getForm();
    }

    private function homeHelpForm($table_data)
    {
        // build the form
        $form_builder = $this->createFormBuilder(['value' => json_encode($table_data)]);
        $form_builder->add('value', 'hidden');

        return $form_builder->getForm();
    }

    private function getHomehelpDefaults(\DateTime $month, array $rowHeaders)
    {
        $day_count = $month->format('t');

        $re = [
//            'stretchH' => 'all',
            'minSpareRows'          => 0,
            'cells'                 => true, // use the cells function in Jcssettings to format the table nicely
            'sums'                  => true, // use the sums function in Jcssettings to refresh the sums
            'readOnlyCellClassName' => 'hh-readonly',
            'colWidths'             => [],
            'colHeaders'            => [],
            'rowHeaders'            => $rowHeaders,
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
        $re['colHeaders'][] = 'Össz';
        $re['colWidths'][]  = 50;
        $re['columns'][]    = [
            'data'     => $d,
            'type'     => 'text',
            'language' => 'hu',
        ];

        return json_encode($re);
    }

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
}
