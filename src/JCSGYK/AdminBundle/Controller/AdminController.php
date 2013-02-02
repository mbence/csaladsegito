<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

use JCSGYK\AdminBundle\Entity\AdminUser;
use JCSGYK\AdminBundle\Form\Type\AdminUserType;

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
     * Lists the users from the admin_user table
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function usersAction()
    {
        $em = $this->getDoctrine()->getManager();
//        $users = $em->getRepository('JCSGYKAdminBundle:User')->findAll([], ['lastname, firstname' => 'ASC']);
        $users = $em->createQuery('SELECT u FROM JCSGYKAdminBundle:User u ORDER BY u.lastname, u.firstname')
            ->getResult();

        return $this->render('JCSGYKAdminBundle:Admin:users.html.twig', ['users' => $users]);
    }

    /**
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