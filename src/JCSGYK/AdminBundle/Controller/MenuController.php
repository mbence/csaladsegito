<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MenuController extends Controller
{
    protected $menu = [
            ['route' => 'assistance_home', 'label' => 'Asszisztencia', 'role' => 'ROLE_ASSISTANCE', 'submenu' => [
                ['route' => 'assistance_home', 'label' => 'Keresés', 'role' => 'ROLE_ASSISTANCE'],
//                ['route' => 'new_person', 'label' => 'Új ügyfél', 'role' => 'ROLE_ASSISTANCE'],
            ]],
            ['route' => 'family_home', 'label' => 'Családsegítő', 'role' => 'ROLE_FAMILY_SUPPORT'],
            ['route' => 'child_home', 'label' => 'Gyermekjólét', 'role' => 'ROLE_CHILD_WELFARE'],
            ['route' => 'admin_home', 'label' => 'Admin', 'role' => 'ROLE_ADMIN', 'submenu' => [
                ['route' => 'admin_users', 'label' => 'Felhasználók', 'role' => 'ROLE_ADMIN'],
                ['route' => 'admin_update', 'label' => 'Rendszerfrissítés', 'role' => 'ROLE_SUPERADMIN'],
                ['route' => 'jcsgyk_dbimport_homepage', 'label' => 'Adatbázis Import', 'role' => 'ROLE_SUPERADMIN'],
            ]],
        ];    
    
    public function mainAction()
    {
        $router = $this->get("router");
        $route = $router->match($this->getRequest()->getPathInfo());
        
        return $this->render('JCSGYKAdminBundle:Elements:menu.html.twig', ['menu_items' => $this->menu, 'route' => $route['_route']]);
    }
    
    /**
     * Redirects the user based on her roles (assistance, user or admin)
     */
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
    
    /**
     * Renders the breadcrumb element
     */
    public function breadcrumbAction()
    {
        $router = $this->get("router");
        $route = $router->match($this->getRequest()->getPathInfo());
        
        $path = $this->findPath($route['_route'], $this->menu);
        
        // Add the homepage element
        array_unshift($path, ['route' => 'home', 'label' => 'Főoldal']);
        
        return $this->render('JCSGYKAdminBundle:Elements:breadcrumb.html.twig', array('breadcrumb' => $path));
    }
    
    /**
     * Finds the given route in the menu recursively
     * 
     * @param string $route the route to find
     * @param array $menu menu items and submenu
     * @return array the route and label of the matched path
     */
    protected function findPath($route, $menu)
    {
        foreach ($menu as $m) {
            if ($m['route'] == $route) {

                return [['route' => $m['route'], 'label' => $m['label']]];
            }
            elseif (isset($m['submenu'])) {
                $re = $this->findPath($route, $m['submenu']);
                if (!empty($re)) {
                    array_unshift($re, ['route' => $m['route'], 'label' => $m['label']]);
                            
                    return $re;
                }                
            }
        } 
        
        return [];
    }
}