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
    public function usersAction($id, Request $request)
    {
        $user = null;
        $um = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        // only superadmins can see and edit superadmins
        $sql = 'SELECT u FROM JCSGYKAdminBundle:User u';
        if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $sql .= " WHERE u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'";
        }
        $sql .= ' ORDER BY u.lastname, u.firstname';

        $users = $em->createQuery($sql)
            ->getResult();

        if ('new' == $id) {
            // new user
            $user = $um->createUser();
            $user->setCompanyId($company_id);
        }
        else {
            $user = $em->getRepository('JCSGYKAdminBundle:User')
                ->findOneBy(['id' => $id, 'companyId' => $company_id]);
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
                    // save the new user data
                    if ('new' == $id) {
                        $em->persist($user);
                    }
                    $em->flush();

                    $this->get('session')->setFlash('notice', 'felhasználó elmentve');

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
     * Lists the params from the parameters table
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function paramsAction($group, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $co = $this->container->get('jcs.ds')->getCompanyId();

        // save the current param group
        if ($request->isMethod('POST')) {
            $paramsave = $request->request->get('parameter');

            foreach ($paramsave as $param_id => $param) {
                if (!empty($param['id'])) {
                    // update a parameter
                    try {
                        //get the original param
                        $orig = $em->createQuery('SELECT p FROM JCSGYKAdminBundle:Parameter p WHERE p.id=:id AND p.companyId=:company')
                            ->setParameter('id', $param['id'])
                            ->setParameter('company', $co)
                            ->setMaxResults(1)
                            ->getSingleResult();
                    }
                    catch (\Exception $e) {
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
            $this->get('session')->setFlash('notice', 'paraméterek elmentve');
            return $this->redirect($this->generateUrl('admin_params', ['group' => $request->request->get('group', 1)]));
        }

        // get all params
        $params = $this->get('jcs.ds')->getAll();
        $groups = $this->container->getParameter('param_groups');

        return $this->render('JCSGYKAdminBundle:Admin:params.html.twig', ['params' => $params, 'groups' => $groups, 'act' => $group]);
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