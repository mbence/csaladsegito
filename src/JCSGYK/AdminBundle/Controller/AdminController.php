<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

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

class AdminController extends Controller
{
    /**
    * @Secure(roles="ROLE_ADMIN")
    */
    public function indexAction()
    {
        $co = $this->container->get('jcs.ds')->getCompany();

        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $this->get('logger')->info('ROLE_ADMIN');
        }

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
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        // only superadmins can see and edit superadmins
        $sql = 'SELECT u FROM JCSGYKAdminBundle:User u WHERE u.companyId=:company ';
        if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $sql .= " AND u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'";
        }
        $sql .= ' ORDER BY u.lastname, u.firstname';

        $users = $em->createQuery($sql)
            ->SetParameter('company', $company_id)
            ->getResult();

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

        return $this->render('JCSGYKAdminBundle:Admin:users.html.twig', ['users' => $users, 'form' => $form_view, 'user' => $user, 'id' => $id]);
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
        $co = $this->container->get('jcs.ds')->getCompanyId();

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

            return $this->redirect($this->generateUrl('admin_paramgroups', ['type' => $request->request->get('group', 0)]));
        }

        // get all paramgroup types
        $types = $this->get('jcs.ds')->getGroupTypes(false);
        $groups = $this->get('jcs.ds')->getParamgroups();

        return $this->render('JCSGYKAdminBundle:Admin:paramgroups.html.twig', ['groups' => $groups, 'types' => $types, 'act' => $type]);
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
        $co = $this->container->get('jcs.ds')->getCompanyId();

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
                    'required' => $sg[1],
                    'control' => $sg[2]
                ];
            }
        }
        else {
            $groups = $this->get('jcs.ds')->getParamGroup();
            foreach ($groups as $g) {
                $all_groups[] = [
                    'id' => $g->getId(),
                    'name' => $g->getName(),
                    'type' => $g->getType(),
                    'required' => $g->getRequired(),
                    'control' => $g->getControl()
                ];
            }
        }
        // get all params
        $params = $this->get('jcs.ds')->getAll();

        // get all paramgroup types
        $types = $this->get('jcs.ds')->getGroupTypes($sys);

        return $this->render('JCSGYKAdminBundle:Admin:params.html.twig', [
            'params' => $params,
            'groups' => $all_groups,
            'act' => $group,
            'types' => $types,
            'route' => $route
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

        if ('new' == $id) {
            // new company
            $company = new Company;
        }
        elseif (!is_null($id)) {
            $company = $em->getRepository('JCSGYKAdminBundle:Company')->find($id);
        }

        if (is_null($id) || !empty($company)) {

            if (!empty($company)) {
                $form = $this->createForm(new CompanyType(), $company);
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
                    $client_types = array_flip($this->container->get('jcs.ds')->getAllClientTypes());
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
                        $this->get('jcs.seq')->reset(['id' => $company->getId(), 'sequencePolicy' => $company->getSequencePolicy()]);
                        }
                        else {
                            // update the sequence for the policy change
                        if ($original_policy != $company->getSequencePolicy()) {
                            $year = $company->getSequencePolicy() == Company::BY_YEAR ? date('Y') : null;
                            $this->get('jcs.seq')->setYear(['id' => $company->getId(), 'sequencePolicy' => $company->getSequencePolicy()], $year);
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

            return $this->render('JCSGYKAdminBundle:Admin:companies.html.twig', ['companies' => $companies, 'id' => $id, 'act' => $company, 'form' => $form_view]);
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

        if ('new' == $id) {
            // new club
            $club = new Club;
        }
        elseif (!is_null($id)) {
            $club = $em->getRepository('JCSGYKAdminBundle:Club')->find($id);
        }

        if (is_null($id) || !empty($club)) {

            if (!empty($club)) {
                $form = $this->createForm(new ClubType(), $club);
            }

            // save the current club
            if ($request->isMethod('POST')) {

                $form->bind($request);

                if ($form->isValid()) {

                    if (is_null($club->getId())) {
                        // get the current company id from the datatore
                        $company_id = $this->container->get('jcs.ds')->getCompanyId();
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
            $clubs = $em->getRepository('JCSGYKAdminBundle:Club')->findBy([], ['name' => 'ASC']);

            return $this->render('JCSGYKAdminBundle:Admin:clubs.html.twig', ['clubs' => $clubs, 'id' => $id, 'act' => $club, 'form' => $form_view]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
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

        if ($request->isMethod('POST') && $request->get('update')) {
            // git pull
            $ex[] = "git pull https://github.com/mbence/csaladsegito.git master";
            // clear cache
            $ex[] = "php app/console cache:clear --env=prod --no-debug";
            // assetic dump
            //$ex[] = "php app/console assetic:dump --env=prod --no-debug";
            // asset install
            //$ex[] = "php app/console assets:install";

            // switch to symfony root dir
            chdir($this->get('kernel')->getRootDir() . '/..');

            foreach ($ex as $com) {
                $output = '';
                $return_val = '';
                exec($com . ' 2>&1', $output, $return_val);
                $re [] = ['ex' => $com, 'return_val' => $return_val, 'output' => implode($output, '<br>')];
            }

            $session->set('update', $re);

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

            $response = new Response();

            $response->headers->set('Content-Type', $template->getMimeType());
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $template->getOriginalName());

            $response->setContent(file_get_contents($docpath));

            return $response;
        }
        else {
            throw new HttpException(400, "Bad request");
        }
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
}