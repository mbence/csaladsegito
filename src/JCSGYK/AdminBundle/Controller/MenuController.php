<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MenuController extends Controller
{
    public function mainAction()
    {
        $menu_items = [
            ['route' => 'assistance_home', 'label' => 'Asszisztencia', 'role' => 'ROLE_ASSISTANCE'],
            ['route' => 'family_home', 'label' => 'Családsegítő', 'role' => 'ROLE_FAMILY_SUPPORT'],
            ['route' => 'child_home', 'label' => 'Gyermekjólét', 'role' => 'ROLE_CHILD_WELFARE'],
            ['route' => 'admin_home', 'label' => 'Admin', 'role' => 'ROLE_ADMIN', 'submenu' => [
                ['route' => 'admin_users', 'label' => 'Felhasználók', 'role' => 'ROLE_ADMIN']
            ]],
        ];
        
        return $this->render('JCSGYKAdminBundle:Menu:main.html.twig', array('menu_items' => $menu_items));
    }
    
    public function loginRedirectorAction()
    {
        $sec = $this->get('security.context');
        
        if ($sec->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('admin_home'), 301);
        } 
        elseif ($sec->isGranted('ROLE_FAMILY_SUPPORT')) {
            return $this->redirect($this->generateUrl('family_home'), 301);
        } 
        elseif ($sec->isGranted('ROLE_CHILD_WELFARE')) {
            return $this->redirect($this->generateUrl('child_home'), 301);
        } 
        elseif ($sec->isGranted('ROLE_ASSISTANCE')) {
            return $this->redirect($this->generateUrl('assistance_home'), 301);
        }
    }
    
}