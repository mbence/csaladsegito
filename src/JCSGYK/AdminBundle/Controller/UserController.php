<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;

class UserController extends Controller
{
    /**
    * @Secure(roles="ROLE_ADMIN")
    */

    public function indexAction()
    {
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            $this->get('logger')->info('ROLE_USER');
        }

        // to add user roles:
//        $userManager = $this->get('fos_user.user_manager');
//        $user = $userManager->findUserByUsername('bence');
//        $user->addRole('ROLE_SUPER_ADMIN');
//        $userManager->updateUser($user);


        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', []);
    }
}