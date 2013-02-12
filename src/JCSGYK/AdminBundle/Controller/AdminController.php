<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\User;
use JCSGYK\AdminBundle\Form\Type\UserType;

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
     * Edits the users from the admin_user table
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function userEditAction($id, Request $request)
    {
        // TODO: form action
        //
        //var_dump($_POST);

        $um = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        // only process ajax requests on prod env!
        if ($request->isXmlHttpRequest() || 'dev' == $this->container->getParameter('kernel.environment')) {

            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            if (empty($id)) {
                // new user
                $user = $um->createUser();
                $user->setCompanyId($company_id);
                $user->setPlainPassword('x');
                $form_action = $this->generateUrl('admin_user_new');
            }
            else {
                // get user data
                $user = $em->getRepository('JCSGYKAdminBundle:User')
                    ->findOneBy(['id' => $id, 'companyId' => $company_id]);
                $form_action = $this->generateUrl('admin_user_edit', ['id' => $id]);
            }

            if (!empty($user)) {
                $sec = $this->get('security.context');
                $form = $this->createForm(new UserType($sec), $user);
                // only superadmins can see and edit superadmins
                if (!$sec->isGranted('ROLE_SUPER_ADMIN') && in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                    throw new HttpException(401, "Unauthorized access");
                }
                // save the user
                if ($request->isMethod('POST')) {
                    $form->bind($request);

                    if ($form->isValid()) {
                        // save the user data
                        if (empty($id)) {
                            $em->persist($user);
                        }
                        $em->flush();

                        $this->get('session')->setFlash('notice', 'felhasználó elmentve');
                        return new Response('success');
                    }
                }

                return $this->render('JCSGYKAdminBundle:Admin:useredit.html.twig', ['form' => $form->createView(), 'user' => $user, 'action' => $form_action]);
            }
        }
        throw new HttpException(400, "Bad request");
    }

    /**
     * Lists the users from the admin_user table
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function usersAction(Request $request)
    {
        // only superadmins can see and edit superadmins
        $sql = 'SELECT u FROM JCSGYKAdminBundle:User u';
        if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $sql .= " WHERE u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'";
        }
        $sql .= ' ORDER BY u.lastname, u.firstname';
        $em = $this->getDoctrine()->getManager();
        $users = $em->createQuery($sql)
            ->getResult();

        return $this->render('JCSGYKAdminBundle:Admin:users.html.twig', ['users' => $users]);
    }

    /**
     * Lists the params from the parameters table
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function paramsAction(Request $request)
    {
        $params = $this->get('jcs.ds')->getAll();
        $groups = $this->container->getParameter('param_groups');

        //var_dump($groups);
        if ($request->isMethod('POST')) {
            $paramsave = $request->request->get('parameter');
            foreach ($paramsave as $param_id => $param) {
                var_dump($param);
            }
        }

        return $this->render('JCSGYKAdminBundle:Admin:params.html.twig', ['params' => $params, 'groups' => $groups]);
    }

    /**
     * System update action
     *
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function updateAction(Request $request)
    {
        $ex = [];
        $re = [];
        $session = $this->get('session');

        if ($request->isMethod('POST') && $request->get('update')) {
            // git pull
            $ex[] = "git pull https://github.com/mbence/csaladsegito.git master";
            // clear cache
            $ex[] = "php app/console cache:clear --env=prod --no-debug";
            // assetic dump
            $ex[] = "php app/console assetic:dump --env=prod --no-debug";
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
}