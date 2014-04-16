<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Entity\Event;

class MenuController extends Controller
{
    public function mainAction()
    {
        $items = [
            ['route' => 'clients', 'options' => ['client_type' => 'fh'], 'label' => 'Családgondozó', 'role' => 'ROLE_USER'],
            ['route' => 'clients', 'options' => ['client_type' => 'cw'], 'label' => 'Gyermekjólét', 'role' => 'ROLE_USER'],
            ['route' => 'clients', 'options' => ['client_type' => 'ca'], 'label' => 'Étkeztetés', 'role' => 'ROLE_USER'],
            ['route' => 'settings', 'label' => 'Beállítások', 'role' => 'ROLE_USER'],
        ];

        $menu = $this->checkMenu($items);

        return $this->render('JCSGYKAdminBundle:Elements:menu.html.twig', ['menu' => $menu]);
    }

    public function settingsAction()
    {
        $items = [
            ['route' => 'settings', 'label' => 'Beállítások', 'role' => 'ROLE_USER'],
        ];

        $menu = $this->checkMenu($items);

        return $this->render('JCSGYKAdminBundle:Settings:menu.html.twig', ['menu' => $menu]);

    }

    public function adminSettingsAction()
    {
        $items = [
            ['route' => 'admin_companies', 'label' => 'Cégek', 'role' => 'ROLE_SUPER_ADMIN'],
            ['route' => 'admin_users', 'label' => 'Felhasználók', 'role' => 'ROLE_ADMIN'],
            ['route' => 'admin_clubs', 'label' => 'Klubok', 'role' => 'ROLE_ADMIN'],
            ['route' => 'admin_paramgroups', 'label' => 'Paraméter Csoportok', 'role' => 'ROLE_SUPER_ADMIN'],
            ['route' => 'admin_params', 'label' => 'Paraméterek', 'role' => 'ROLE_ADMIN'],
            ['route' => 'admin_providers', 'label' => 'Szolgáltatók', 'role' => 'ROLE_ADMIN'],
            ['route' => 'admin_templates', 'label' => 'Nyomtatványok', 'role' => 'ROLE_ADMIN'],
            ['route' => 'admin_update', 'label' => 'Rendszerfrissítés', 'role' => 'ROLE_SUPER_ADMIN'],
        ];

        // add the db import menu only for the dev enver
        if ('dev' == $this->container->getParameter('kernel.environment')) {
            $items[] = ['route' => 'jcsgyk_dbimport_homepage', 'label' => 'Adatbázis Import', 'role' => 'ROLE_SUPER_ADMIN'];
        }

        $menu = $this->checkMenu($items);

        return $this->render('JCSGYKAdminBundle:Settings:menu.html.twig', ['menu' => $menu]);

    }

    public function clientAction(Client $client)
    {
        $sec = $this->get('security.context');
        // true if only assistance roles are present
        $assistance = $sec->isGranted('ROLE_ASSISTANCE') && !$sec->isGranted('ROLE_FAMILY_HELP') && !$sec->isGranted('ROLE_CHILD_WELFARE') && !$sec->isGranted('ROLE_CATERING');

        $items = [
            // client_histroy - Template:history
            [
                'url'   => $this->generateUrl('client_history', ['id' => $client->getId()]),
                'label' => 'esettörténet',
                'title' => 'Esettörténet',
                'class' => '',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => $client->canEdit($sec)
            ],
            // archive_client - Client:archive
            [
                'url'   => $this->generateUrl('client_archive', ['id' => $client->getId()]),
                'label' => 'archiválás',
                'title' => 'Ügyfél archiválása',
                'class' => 'archive_client',
                'more'  => true,
                'role'  => 'ROLE_ADMIN',
                'requirement' => $client->getIsArchived() == 0
            ],
            // reopen_client - Client:archive
            [
                'url'   => $this->generateUrl('client_archive', ['id' => $client->getId()]),
                'label' => 'újranyitás',
                'title' => 'Ügyfél újranyitása',
                'class' => 'archive_client',
                'more'  => true,
                'role'  => new Expression('hasRole("ROLE_ADMIN") or hasRole("ROLE_ASSISTANCE")'),
                'requirement' => $client->getIsArchived() == 1
            ],
            // edit_client - Client:edit
            // this is the main action for familiy help, but its only secondary for assistance
            [
                'url'   => $this->generateUrl('client_edit', ['id' => $client->getId()]),
                'label' => 'szerkesztés',
                'title' => 'Ügyfél szerkesztése',
                'class' => 'edit_client ' . ($assistance ? 'greybutton' : 'button'),
                'more'  => $assistance ? true: false,
                'role'  => 'ROLE_USER',
                'requirement' => $client->canEdit($sec)
            ],
            // client_visit - Client:visit
            // this is the main action for assistance
            [
                'url'   => $this->generateUrl('client_visit', ['id' => $client->getId()]),
                'label' => 'megkeresés',
                'title' => 'Ügyfél megkeresés',
                'class' => 'button client_visit',
                'more'  => false,
                'role'  => 'ROLE_ASSISTANCE',
                'requirement' => $client->getIsArchived() == 0
            ]
        ];

        return $this->subMenu($items);
    }

    public function problemAction(Problem $problem)
    {
        $sec = $this->get('security.context');
        $items = [
            // templates
            [
                'url'   => $this->generateUrl('templates', ['id' => $problem->getId()]),
                'label' => 'nyomtatványok',
                'title' => 'Nyomtatványok készítése',
                'class' => 'templates',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => $problem->canEdit($sec) && $problem->getIsActive()
            ],
            // close problem
            [
                'url'   => $this->generateUrl('problem_close', ['id' => $problem->getId()]),
                'label' => 'lezárás',
                'title' => 'Probléma lezárása',
                'class' => 'close_problem',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => !$problem->getClient()->getIsArchived() && $problem->canEdit($sec) && $problem->getIsActive()
            ],
            // confirm problem
            [
                'url'   => $this->generateUrl('problem_confirm', ['id' => $problem->getId()]),
                'label' => 'jóváhagyás',
                'title' => 'Probléma lezárásának jóváhagyása',
                'class' => 'button confirm_problem',
                'more'  => false,
                'role'  => 'ROLE_ADMIN',
                'requirement' => !$problem->getClient()->getIsArchived() && $problem->canEdit($sec) && !$problem->getIsActive() && !$problem->getConfirmer()
            ],
            // reopen problem
            [
                'url'   => $this->generateUrl('problem_close', ['id' => $problem->getId()]),
                'label' => 'újranyitás',
                'title' => 'Probléma újranyitása',
                'class' => 'close_problem',
                'more'  => true,
                'role'  => 'ROLE_ADMIN',
                'requirement' => !$problem->getClient()->getIsArchived() && !$problem->getIsActive()
            ],
            // delete problem
            [
                'url'   => $this->generateUrl('problem_delete', ['id' => $problem->getId()]),
                'label' => 'törlés',
                'title' => 'Probléma törlése',
                'class' => 'delete_problem redtext',
                'more'  => true,
                'role'  => 'ROLE_ADMIN',
                'requirement' => $problem->canEdit($sec) && $problem->getIsActive()
            ],
            // agreement
            [
                'url'   => $this->generateUrl('problem_agreement', ['id' => $problem->getId()]),
                'label' => 'megállapodás',
                'title' => 'Megállapodás',
                'class' => 'problem_agreement',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => $problem->canEdit($sec) && $problem->getIsActive()
            ],
            // edit problem
            [
                'url'   => $this->generateUrl('problem_edit', ['client_id' => $problem->getClient()->getId(), 'id' => $problem->getId()]),
                'label' => 'szerkesztés',
                'title' => 'Probléma szerkesztése',
                'class' => 'button edit_problem',
                'more'  => false,
                'role'  => 'ROLE_USER',
                'requirement' => $problem->canEdit($sec) && $problem->getIsActive()
            ],
        ];

        return $this->subMenu($items);
    }

    public function eventAction(Event $event)
    {
        $sec = $this->get('security.context');
        $items = [
            // delete event
            [
                'url'   => $this->generateUrl('event_delete', ['id' => $event->getId()]),
                'label' => 'törlés',
                'title' => 'Esemény törlése',
                'class' => 'delete_event redtext',
                'more'  => true,
                'role'  => 'ROLE_USER',
                'requirement' => $event->canEdit($sec)
            ],
            // edit event
            [
                'url'   => $this->generateUrl('event_edit', ['id' => $event->getId(), 'problem_id' => $event->getProblem()->getId()]),
                'label' => 'szerkesztés',
                'title' => 'Esemény szerkesztése',
                'class' => 'button edit_event',
                'more'  => false,
                'role'  => 'ROLE_USER',
                'requirement' => $event->canEdit($sec)
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
    protected function checkMenu($items)
    {
        $request = Request::createFromGlobals();

        $url = $request->getPathInfo();
        $route = $this->get("router")->match($url);
        $sec = $this->get('security.context');

        $user_menu = [];
        foreach ($items as $m) {
            $class_list = [];
            if (!empty($route['_route']) && $m['route'] == $route['_route']) {
                $class_list[] = 'current';
            }
            if (in_array($m['role'], ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])) {
                $class_list[] = 'adm-menu';
            }
            $m['class'] = implode(' ', $class_list);
            if ($sec->isGranted($m['role'])) {
                // if options is not defined, add to the array
                if (!isset($m['options'])) {
                    $m['options'] = [];
                }
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