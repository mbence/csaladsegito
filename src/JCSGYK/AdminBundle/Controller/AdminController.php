<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\AdminUser;
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
     * Lists and edits the users from the admin_user table
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function usersAction(Request $request)
    {
        //var_dump($_POST);

        $um = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        // only process ajax requests on prod env!
        $id = $request->request->get('id');
        if (!empty($id) && ($request->isXmlHttpRequest() || 'dev' == $this->container->getParameter('kernel.environment'))) {

            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            // get client data
            $user = $em->getRepository('JCSGYKAdminBundle:User')
                ->findOneBy(['id' => $id, 'companyId' => $company_id]);

            if (!empty($user)) {
                $form = $this->createForm(new UserType(), $user);

                //var_dump($request->request->get('user'));
                # save the user
                if ($request->request->get('user')) {
                    $form->bind($request);

                    if ($form->isValid()) {
                        $em->flush();

                        $this->get('session')->setFlash('notice', 'felhasználó elmentve');
                        return new Response('success');
                    }
                }

                return $this->render('JCSGYKAdminBundle:Admin:useredit.html.twig', ['form' => $form->createView(), 'user' => $user]);
            }
            else {
                throw new HttpException(400, "Bad request");
            }
        }

        // no id given - list all users
        $users = $em->createQuery('SELECT u FROM JCSGYKAdminBundle:User u ORDER BY u.lastname, u.firstname')
            ->getResult();

        return $this->render('JCSGYKAdminBundle:Admin:users.html.twig', ['users' => $users]);
    }

    /**
     * System update action
     *
     * @Secure(roles="ROLE_SUPERADMIN")
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