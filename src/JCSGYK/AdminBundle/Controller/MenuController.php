<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Entity\Event;

class MenuController extends Controller
{
    protected $menu = [
        ['route' => 'clients', 'label' => 'Ügyfelek', 'role' => 'ROLE_USER'],
        ['route' => 'admin_users', 'label' => 'Felhasználók', 'role' => 'ROLE_ADMIN'],
        ['route' => 'admin_params', 'label' => 'Paraméterek', 'role' => 'ROLE_ADMIN'],
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

    public function clientAction(Client $client)
    {
        $items = [
            // client_histroy
            [
                'url'   => $this->generateUrl('client_history', ['id' => $client->getId()]),
                'label' => 'esettörténet',
                'title' => 'Esettörténet',
                'class' => '',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => true
            ],
            // archive_client
            [
                'url'   => $this->generateUrl('client_archive', ['id' => $client->getId()]),
                'label' => 'archiválás',
                'title' => 'Ügyfél archiválása',
                'class' => 'archive_client',
                'more'  => true,
                'role'  => 'ROLE_ADMIN',
                'requirement' => $client->getIsArchived() == 0
            ],
            // reopen_client
            [
                'url'   => $this->generateUrl('client_archive', ['id' => $client->getId()]),
                'label' => 'újranyitás',
                'title' => 'Ügyfél újranyitása',
                'class' => 'archive_client',
                'more'  => true,
                'role'  => 'ROLE_ADMIN',
                'requirement' => $client->getIsArchived() == 1
            ],
            // edit_client
            [
                'url'   => $this->generateUrl('client_edit', ['id' => $client->getId()]),
                'label' => 'szerkesztés',
                'title' => 'Ügyfél szerkesztése',
                'class' => 'button edit_client',
                'more'  => false,
                'role'  => 'ROLE_USER',
                'requirement' => $client->getIsArchived() == 0
            ]
        ];

        return $this->subMenu($items);
    }

    public function problemAction(Problem $problem)
    {
        $items = [
            // problem_history
            [
                'url'   => $this->generateUrl('client_history', ['id' => $problem->getClient()->getId(), 'problem_id' => $problem->getId()]),
                'label' => 'esettörténet',
                'title' => 'Esettörténet',
                'class' => '',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => true
            ],
            // close problem
            [
                'url'   => $this->generateUrl('problem_close', ['id' => $problem->getId()]),
                'label' => 'lezárás',
                'title' => 'Probléma lezárása',
                'class' => 'close_problem',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => !$problem->getClient()->getIsArchived() && $problem->getIsActive()
            ],
            // reopen problem
            [
                'url'   => $this->generateUrl('problem_close', ['id' => $problem->getId()]),
                'label' => 'újranyitás',
                'title' => 'Probléma újranyitása',
                'class' => 'close_problem',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => !$problem->getClient()->getIsArchived() && !$problem->getIsActive()
            ],
            // delete problem
            [
                'url'   => $this->generateUrl('problem_delete', ['id' => $problem->getId()]),
                'label' => 'törlés',
                'title' => 'Probléma törlése',
                'class' => 'delete_problem redtext',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => $problem->getIsActive()
            ],
            // edit problem
            [
                'url'   => $this->generateUrl('problem_edit', ['client_id' => $problem->getClient()->getId(), 'id' => $problem->getId()]),
                'label' => 'szerkesztés',
                'title' => 'Probléma szerkesztése',
                'class' => 'button edit_problem',
                'more'  => false,
                'role'  => 'ROLE_USER',
                'requirement' => $problem->getIsActive()
            ],
        ];

        return $this->subMenu($items);
    }

    public function eventAction(Event $event)
    {
        $items = [
            // delete event
            [
                'url'   => $this->generateUrl('event_delete', ['id' => $event->getId()]),
                'label' => 'törlés',
                'title' => 'Esemény törlése',
                'class' => 'delete_event redtext',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => $event->getProblem()->getIsActive()
            ],
            // edit event
            [
                'url'   => $this->generateUrl('event_edit', ['id' => $event->getId(), 'problem_id' => $event->getProblem()->getId()]),
                'label' => 'szerkesztés',
                'title' => 'Esemény szerkesztése',
                'class' => 'button edit_event',
                'more'  => false,
                'role'  => 'ROLE_USER',
                'requirement' => $event->getProblem()->getIsActive()
            ],
        ];

        return $this->subMenu($items);
    }

    protected function subMenu($items)
    {
        $sec = $this->get('security.context');
        $menu = [];
        $more = [];

        foreach ($items as $item) {
            if ($sec->isGranted($item['role']) && $item['requirement']) {
                if (empty($item['more'])) {
                    $menu[] = $item;
                }
                else {
                    $more[] = $item;
                }
            }
        };

        return $this->render('JCSGYKAdminBundle:Elements:submenu.html.twig', ['menu' => $menu, 'more' => $more]);
    }

    /**
     * Filters the menu with the user rights
     * Finds the active path, and sets it the class
     * Adds extra classes for admin role menu items
     * @return array Menu
     */
    protected function checkMenu()
    {
        $request = Request::createFromGlobals();

        $url = $request->getPathInfo();
        $route = $this->get("router")->match($url);
        $sec = $this->get('security.context');

        $user_menu = [];
        foreach ($this->menu as $m) {
            $class_list = [];
            if (!empty($route['_route']) && $m['route'] == $route['_route']) {
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
            return $this->redirect($this->generateUrl('home'), 301);
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

        return $this->redirect($this->generateUrl('home'), 301);
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