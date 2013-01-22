<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends Controller
{
    protected $menu = [
        ['route' => 'clients', 'label' => 'Ügyfelek', 'role' => 'IS_AUTHENTICATED_FULLY', 'bgpos' => '-120px -6px'],
//       ['route' => 'new_client', 'label' => 'Új ügyfél', 'role' => 'ROLE_ASSISTANCE', 'bgpos' => '-200px -166px'],

        ['route' => 'admin_users', 'label' => 'Felhasználók', 'role' => 'ROLE_ADMIN', 'bgpos' => '-241px -166px'],
        ['route' => 'admin_update', 'label' => 'Rendszerfrissítés', 'role' => 'ROLE_SUPERADMIN', 'bgpos' => '-520px -326px'],
        ['route' => 'jcsgyk_dbimport_homepage', 'label' => 'Adatbázis Import', 'role' => 'ROLE_SUPERADMIN', 'bgpos' => '-160px -246px'],
    ];

    public function mainAction()
    {
        $router = $this->get("router");
        $this->setActivePath();
        $user_menu = $this->checkMenu();

        return $this->render('JCSGYKAdminBundle:Elements:menu.html.twig', ['menu' => $user_menu]);
    }

    /**
     * Filters the menu with the user rights
     * @return type
     */
    protected function checkMenu()
    {
        $sec = $this->get('security.context');

        $user_menu = [];

        foreach ($this->menu as $m) {
            if ($sec->isGranted($m['role'])) {
                $user_menu[] = $m;
            }
        }

        return $user_menu;
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
        elseif ($sec->isGranted('ROLE_FAMILY_HELP')) {
            return $this->redirect($this->generateUrl('home'), 301);
        }
        elseif ($sec->isGranted('ROLE_CHILD_WELFARE')) {
            return $this->redirect($this->generateUrl('home'), 301);
        }
        elseif ($sec->isGranted('ROLE_ASSISTANCE')) {
            return $this->redirect($this->generateUrl('clients'), 301);
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
     * Finds the active path, and sets it in $this->menu
     */
    protected function setActivePath()
    {
        $router = $this->get("router");
        $r = $router->match($this->getRequest()->getPathInfo());
        if (!empty($r['_route'])) {
            $route = $r['_route'];

            foreach ($this->menu as $mkey => $m) {
                if ($m['route'] == $route) {
                    $this->menu[$mkey]['active'] = 1;
                }
            }
        }
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