<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MenuController extends Controller
{
    protected $menu = [
        'assistance' => ['route' => 'assistance_home', 'label' => 'Asszisztencia', 'role' => 'ROLE_ASSISTANCE', 'items' => [
            ['route' => 'assistance_home', 'label' => 'Keresés', 'role' => 'ROLE_ASSISTANCE', 'bgpos' => '-120px -6px'],
//            ['route' => 'new_person', 'label' => 'Új ügyfél', 'role' => 'ROLE_ASSISTANCE', 'bgpos' => '-200px -166px'],
        ]],
        'family' => ['route' => 'family_home', 'label' => 'Családsegítő', 'role' => 'ROLE_FAMILY_HELP', 'items' => [
        ]],
        'child' => ['route' => 'child_home', 'label' => 'Gyermekjólét', 'role' => 'ROLE_CHILD_WELFARE', 'items' => [
        ]],
        'admin' => ['route' => 'admin_home', 'label' => 'Admin', 'role' => 'ROLE_ADMIN', 'items' => [
            ['route' => 'admin_users', 'label' => 'Felhasználók', 'role' => 'ROLE_ADMIN', 'bgpos' => '-241px -166px'],
            ['route' => 'admin_update', 'label' => 'Rendszerfrissítés', 'role' => 'ROLE_SUPERADMIN', 'bgpos' => '-520px -326px'],
            ['route' => 'jcsgyk_dbimport_homepage', 'label' => 'Adatbázis Import', 'role' => 'ROLE_SUPERADMIN', 'bgpos' => '-160px -246px'],
        ]]
    ];

    public function mainAction()
    {
        $router = $this->get("router");
        $this->setActivePath();
        // get inquiry types from the db param service (parameters table)
        $inquiry_types = $this->container->get('jcs.ds')->getGroup(1);
        $user_menu = $this->checkMenu();

        return $this->render('JCSGYKAdminBundle:Elements:menu.html.twig', ['menu' => $user_menu, 'inquiry_types' => $inquiry_types]);
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
                $tmp = $m;
                $tmp['items'] = [];
                if (!empty($m['items'])) {
                    foreach ($m['items'] as $i) {
                        if ($sec->isGranted($i['role'])) {
                            $tmp['items'][] = $i;
                        }
                    }
                }
                $user_menu[] = $tmp;
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
                if (isset($m['items'])) {
                    foreach ($m['items'] as $ikey => $i) {
                        if ($i['route'] == $route) {
                            $this->menu[$mkey]['active'] = 1;
                            $this->menu[$mkey]['items'][$ikey]['active'] = 1;
                        }
                    }
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