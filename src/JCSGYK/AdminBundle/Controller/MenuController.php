<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends Controller
{
    protected $menu = [
        ['route' => 'clients', 'label' => 'Ügyfelek', 'role' => 'ROLE_USER'],
//       ['route' => 'new_client', 'label' => 'Új ügyfél', 'role' => 'ROLE_USER'],

        ['route' => 'admin_users', 'label' => 'Felhasználók', 'role' => 'ROLE_ADMIN'],
        ['route' => 'admin_update', 'label' => 'Rendszerfrissítés', 'role' => 'ROLE_SUPER_ADMIN'],
    ];

    public function mainAction()
    {
        // add the db import menu only for the dev enver
        if ('dev' == $this->container->getParameter('kernel.environment')) {
            $this->menu[] = ['route' => 'jcsgyk_dbimport_homepage', 'label' => 'Adatbázis Import', 'role' => 'ROLE_SUPER_ADMIN'];
        }

        $router = $this->get("router");
        $user_menu = $this->checkMenu();

        return $this->render('JCSGYKAdminBundle:Elements:menu.html.twig', ['menu' => $user_menu]);
    }

    /**
     * Filters the menu with the user rights
     * Finds the active path, and sets it the class
     * Adds extra classes for admin role menu items
     * @return array Menu
     */
    protected function checkMenu()
    {
        $router = $this->get("router");
        $r = $router->match($this->getRequest()->getPathInfo());
        $sec = $this->get('security.context');

        $user_menu = [];
        foreach ($this->menu as $m) {
            $class_list = [];
            if (!empty($r['_route']) && $m['route'] == $r['_route']) {
                $class_list[] = 'current';
            }
            if (in_array($m['role'], ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
                $class_list[] = 'adm-menu';
            }
            $m['class'] = implode(' ', $class_list);
            if ($sec->isGranted($m['role'])) {
                $user_menu[] = $m;
            }
        }

        return $user_menu;
    }

    /**
     * TODO: Remove this, no longer needed
     *
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
     * TODO: Remove this, no longer needed
     *
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
     * TODO: Remove this, no longer needed
     *
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