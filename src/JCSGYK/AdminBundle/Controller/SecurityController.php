<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityController extends Controller
{
    /**
     * Redirects the user based on her roles (assistance, user or admin)
     */
    public function loginRedirectorAction()
    {
        $sec = $this->get('security.context');
        if ($sec->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('admin_home'), 301);
        } 
        elseif ($sec->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('user_home'), 301);
        } 
        elseif ($sec->isGranted('ROLE_ASSISTANCE')) {
            return $this->redirect($this->generateUrl('home'), 301);
        }
    }

    public function loginAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render('JCSGYKAdminBundle:Security:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        ));
    }
}
