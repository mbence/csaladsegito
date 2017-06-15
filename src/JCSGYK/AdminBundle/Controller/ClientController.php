<?php

namespace JCSGYK\AdminBundle\Controller;

use JCSGYK\AdminBundle\Services\DataStore;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Form\FormError;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Form\Type\ClientType;
use JCSGYK\AdminBundle\Entity\Archive;
use JCSGYK\AdminBundle\Form\Type\ArchiveType;
use JCSGYK\AdminBundle\Entity\Task;
use JCSGYK\AdminBundle\Entity\Stat;
use JCSGYK\AdminBundle\Entity\Relation;
use JCSGYK\AdminBundle\Form\Type\RelativeType;
use JCSGYK\AdminBundle\Entity\Catering;
use JCSGYK\AdminBundle\Form\Type\CateringType;
use JCSGYK\AdminBundle\Entity\ClientOrder;
use JCSGYK\AdminBundle\Form\Type\ClientOrderType;
use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Entity\HomeHelp;
use JCSGYK\AdminBundle\Form\Type\HomehelpType;
use JCSGYK\AdminBundle\Entity\MonthlyClosing;

class ClientController extends Controller
{
    /**
     * Starting point for the client menu
     *
     * @Secure(roles="ROLE_USER")
     */
    public function indexAction($client_type = 1, $client_id = null, $problem_id = null)
    {
        // Global security check for client type
        $this->get('jcs.ds')->userRoleCheck($client_type);

        return $this->render('JCSGYKAdminBundle:Client:index.html.twig', [
            'client_type' => $client_type,
            'client_id' => $client_id,
            'problem_id' => $problem_id
        ]);
    }

    /**
     * Register a client visit
     *
     * @Secure(roles="ROLE_ASSISTANCE")
     * @param Request $request
     * @param null $id
     */
    public function visitAction(Request $request, $id = null)
    {
        // find the users. We need the case admin first, then the assignees of the problems, and then everyone else
        // only active users will be displayed

        if (!empty($id)) {

            $em = $this->getDoctrine()->getManager();
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            $ae = $this->container->get('jcs.twig.adminextension');
            $sec = $this->get('security.context');
            $user= $sec->getToken()->getUser();

            $userlist = [];

            $user_counts = [
                'case_admin' => 0,
                'assignees' => 0,
                'all' => 0
            ];
            $listed_user_ids = [];

            // get the client
            $client = $this->getClient($id);

            // Global security check for user type
            $this->get('jcs.ds')->userRoleCheck($client->getType());

            // case admin
            $ca = $client->getCaseAdmin();
            if (!empty($ca) && $ca->isEnabled()) {
                $userlist[$ca->getId()] = $ae->formatName($ca->getFirstname(), $ca->getLastname());
                $user_counts['case_admin']++;
                $listed_user_ids[] = $ca->getId();
            }
            // problem assignees
            $problems = $client->getProblems();
            foreach ($problems as $problem) {
                $assignee = $problem->getAssignee();
                // we only need a user if not already on the list, and are active
                if (!empty($assignee) &&
                    $assignee->isEnabled() &&
                    !in_array($assignee->getId(), $listed_user_ids))
                {
                    $userlist[$assignee->getId()] = $ae->formatName($assignee->getFirstname(), $assignee->getLastname());
                    $user_counts['assignees']++;
                    $listed_user_ids[] = $assignee->getId();
                }
            }
            // finally we list all active users
            $users = $em->getRepository('JCSGYKAdminBundle:User')
                ->findBy(['enabled' => 1, 'companyId' => $company_id], ['lastname' => 'ASC', 'firstname' => 'ASC']);

            foreach ($users as $user) {
                // superadmins should not appear on the list
                if (!in_array($user->getId(), $listed_user_ids) && !$user->hasRole('ROLE_SUPER_ADMIN')) {
                    $userlist[$user->getId()] = $ae->formatName($user->getFirstname(), $user->getLastname());
                    $user_counts['all']++;
                    $listed_user_ids[] = $user->getId();
                }
            }

            // make the form
            $form_builder = $this->createFormBuilder()
                ->add('userlist', 'choice', [
                    'label' => '',
                    'choices' => $userlist,
                    'expanded' => true,
                    'multiple' => false,
                ]);

            // get the dispatch paramGroup
            $dispatch_list = $this->container->get('jcs.ds')->getGroup('signals');
            if (!empty($dispatch_list)) {
                $form_builder->add('dispatch', 'choice', [
                    'label' => $this->container->getParameter('system_parameter_groups')['signals'][0],
                    'choices' => $dispatch_list,
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                ]);
            }
            $form = $form_builder->getForm();

            // save the visit
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $user= $this->get('security.context')->getToken()->getUser();

                    $assignee = $em->getRepository("JCSGYKAdminBundle:User")->find($data['userlist']);
                    $dispatch = !empty($data['dispatch']) ? $data['dispatch'] : null;

                    $this->saveVisitTask($client, $assignee, $user, $dispatch);

                    $this->get('session')->getFlashBag()->add('notice', 'Megkeresés elmentve');

                    return $this->render('JCSGYKAdminBundle:Dialog:visit.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Dialog:visit.html.twig', ['client' => $client, 'form' => $form->createView(), 'user_counts' => $user_counts]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Get only the catering data of the client.
     * Used with the refreshCatering JS function
     *
     * @Secure(roles="ROLE_USER")
     */
    public function cateringAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
        }

        if (!empty($client)) {

            $ds = $this->container->get('jcs.ds');

            // Global security check for user type
            $ds->userRoleCheck($client->getType());

            $sec = $this->get('security.context');

            return $this->render('JCSGYKAdminBundle:Catering:_catering.html.twig', [
                'client' => $client,
                'catering' => $client->getCatering(),
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Get only the homehelp data of the client.
     * Used with the refreshHomeHelp JS function
     *
     * @Secure(roles="ROLE_USER")
     */
    public function homehelpAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
        }

        if (!empty($client)) {

            $ds = $this->container->get('jcs.ds');

            // Global security check for user type
            $ds->userRoleCheck($client->getType());

            $sec = $this->get('security.context');

            return $this->render('JCSGYKAdminBundle:Homehelp:_homehelp.html.twig', [
                'client' => $client,
                'homehelp' => $client->getHomehelp(),
                'club_type_label' => $this->getClubTypeLabel($client),
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Edit catering fields
     *
     * @Secure(roles="ROLE_USER")
     * @param Request $request
     * @param null $id
     */
    public function cateringEditAction(Request $request, $id = null)
    {
        if (!empty($id)) {
            $em         = $this->container->get('doctrine')->getManager();
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            $ae         = $this->container->get('jcs.twig.adminextension');
            $ds         = $this->container->get('jcs.ds');
            $sec        = $this->get('security.context');
            $user       = $sec->getToken()->getUser();

            // get the client
            $client = $this->getClient($id);

            // Global security check for user type
            $ds->userRoleCheck($client->getType());

            // get club list by user role
            $clubs = $ds->getClubs();
            $catering = $client->getCatering();

            if (empty($catering)) {
                $catering = new Catering();
                $catering->setClient($client);
                $client->setCatering($catering);
                // try to set the club
                $my_clubs = $ds->getMyClubs();
                if (!empty($my_clubs)) {
                    $my_first_club = reset($my_clubs);
                    $catering->setClub($my_first_club);
                }
            }
            $original_catering = clone $catering;

            $form = $this->createForm(new CateringType($ds, $clubs), $catering);

            // save the catering data
            $form->handleRequest($request);


            if ($form->isValid()) {
                // save the new Catering record
                if (empty($catering->getId())) {
                    $em->persist($catering);
                }
                // check and fix paused dates
                $this->fixDates($catering);
                $this->updateClientOrders($catering, $original_catering);

                // save client modifier
                $client->setModifier($user);
                $client->setModifiedAt(new \DateTime());

                // save
                $em->flush();

                // turn off the logging for the rest of the process
                $this->container->get('history.logger')->off();

                // update Homehelp record if necessary
                $homehelp = $client->getHomehelp();
                if (!empty($homehelp)) {
                    $homehelp->setClub($catering->getClub());
                    $homehelp->setIncome($catering->getIncome());
                }

                $this->get('session')->getFlashBag()->add('notice', 'Étkeztetés elmentve');

                return $this->render('JCSGYKAdminBundle:Catering:catering_dialog.html.twig', [
                    'success' => true,
                ]);
            }

            return $this->render('JCSGYKAdminBundle:Catering:catering_dialog.html.twig', [
                'client'          => $client,
                'form'            => $form->createView(),
                'clubs_menu_list' => json_encode($ds->getClubsMenuList($clubs)),
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    public function fixdates($record)
    {
        if (empty($record->getPausedFrom())) {
            $record->setPausedTo(null);
        }
        if (!empty($record->getPausedFrom()) && !empty($record->getAgreementTo()) && $record->getPausedFrom() > $record->getAgreementTo()) {
            $record->setPausedFrom(null);
            $record->setPausedTo(null);
        }
        if (!empty($record->getPausedTo()) && !empty($record->getAgreementTo()) && $record->getPausedTo() > $record->getAgreementTo()) {
            $record->setPausedTo($record->getAgreementTo());
        }
        if (!empty($record->getPausedFrom()) && !empty($record->getAgreementFrom()) && $record->getPausedFrom() < $record->getAgreementFrom()) {
            $record->setPausedFrom($record->getAgreementFrom());
        }
    }

    private function updateClientOrders(Catering $new, Catering $old)
    {
        // Users can change the agreement and paused dates to anything, even past dates.
        // We will not touch any order however, that is before the allowed dates ($next_change)

        $em          = $this->container->get('doctrine')->getManager();
        $orders_repo = $em->getRepository("JCSGYKAdminBundle:ClientOrder");
        $client_id   = $new->getClient()->getId();
        $next_change = $this->nextChangeDate();

        // check if menu was changed
        if ($old->getMenu() != $new->getMenu()) {
            // we must update the future orders with this menu!
            $orders_repo->updateMenu($client_id, $next_change, $new->getMenu());
        }

        // check agreement changes
        if ($old->getAgreementFrom() != $new->getAgreementFrom() || $old->getAgreementTo() != $new->getAgreementTo()) {
            // first cancel the old orders, but only if it is in the future
            $this->updateOrders($client_id, 0, $old->getAgreementFrom(), $old->getAgreementTo());

            // then set the new agreement
            $this->updateOrders($client_id, 1, $new->getAgreementFrom(), $new->getAgreementTo());
        }

        // check paused changes
        if ($old->getPausedFrom() != $new->getPausedFrom() || $old->getPausedTo() != $new->getPausedTo()) {
            // first undo the old pause, but only if it is in the future
            $this->updateOrders($client_id, 1, $old->getPausedFrom(), $old->getPausedTo(), $new->getAgreementTo());

            // then set the new pause
            $this->updateOrders($client_id, 0, $new->getPausedFrom(), $new->getPausedTo(), $new->getAgreementTo());
        }
    }

    /**
     * Returns the date of the next possible change
     * Before 10:00 it's tomorrow, after that it's the day after tomorrow
     *
     * @return \DateTime
     */
    private function nextChangeDate()
    {
        $next_change = new \DateTime('tomorrow');
        if (date('H') >= 10) {
            $next_change->modify('+1Day');
        }

        return $next_change;
    }

    /**
     * Update orders to a new status in a given time period
     * @param int $client_id
     * @param \DateTime $from
     * @param \DateTime $to
     */
    private function updateOrders($client_id, $new_status, \DateTime $from = null, \DateTime $to = null, \DateTime $max = null)
    {
        // if no dates were set, we return
        if (empty($from)) {
            return;
        }
        $next_change = $this->nextChangeDate();
        // if this was in the past, we have nothing to do
        if (!empty($to) && $to < $next_change) {
            return;
        }

        // don't change orders in the past
        $update_from = $from >= $next_change ? $from : $next_change;
        $update_to = !empty($to) ? $to : null;
        if (!empty($max) && (empty($update_to) || $update_to > $max)) {
            $update_to = $max;
        }

        $orders_repo = $this->container->get('doctrine')->getManager()->getRepository("JCSGYKAdminBundle:ClientOrder");
        $orders_repo->updateOrders($client_id, $new_status, $update_from, $update_to);
    }

    /**
     * Clone the date, clear the time part, and add one day to it
     * @param \DateTime $date
     * @return \DateTime
     */
    private function nextDay(\DateTime $date)
    {
        $re = clone $date;
        $re->setTime(0, 0, 0)
            ->modify('+1Day');

        return $re;
    }

    /**
     * Edit homehelp fields
     *
     * @Secure(roles="ROLE_USER")
     * @param Request $request
     * @param int $id
     */
    public function homehelpEditAction(Request $request, $id = null)
    {
        if (!empty($id)) {
            $em         = $this->getDoctrine()->getManager();
            /** @var DataStore $ds */
            $ds         = $this->container->get('jcs.ds');
            $sec        = $this->get('security.context');
            $user       = $sec->getToken()->getUser();

            // get the client
            $client = $this->getClient($id);

            // Global security check for user type
            $ds->userRoleCheck($client->getType());

            // get club list by user role
            $clubs = $ds->getClubs();
            $homehelp = $client->getHomehelp();

            if (empty($homehelp)) {
                $homehelp = new HomeHelp();
                $homehelp->setClient($client);
                $client->setHomehelp($homehelp);
                // try to set the club
                $my_clubs = $ds->getMyClubs();
                if (!empty($my_clubs)) {
                    $my_first_club = reset($my_clubs);
                    $homehelp->setClub($my_first_club);
                }
            }

            $form = $this->createForm(new HomehelpType($ds, $clubs), $homehelp);

            // save the catering data
            $form->handleRequest($request);

            if ($form->isValid()) {
                // save the new Homehelp record
                if (empty($homehelp->getId())) {
                    $em->persist($homehelp);
                }
                // check and fix paused dates
                $this->fixDates($homehelp);

                // save homehelp modifier
                $homehelp->setModifier($user);
                $homehelp->setModifiedAt(new \DateTime());

                // save
                $em->flush();

                // turn off the logging for the rest of the process
                $this->container->get('history.logger')->off();

                // update Catering record if necessary
                $catering = $client->getCatering();
                if (!empty($catering) && !empty($homehelp->getIncome())) {
                    $catering->setClub($homehelp->getClub());
                    $catering->setIncome($homehelp->getIncome());
                }

                $this->get('session')->getFlashBag()->add('notice', 'Gondozás elmentve');

                return $this->render('JCSGYKAdminBundle:Homehelp:homehelp_dialog.html.twig', [
                    'success' => true,
                ]);
            }

            return $this->render('JCSGYKAdminBundle:Homehelp:homehelp_dialog.html.twig', [
                'client'          => $client,
                'form'            => $form->createView(),
                'clubs_type_list' => json_encode($ds->getClubsTypeList($clubs)),
                'club_type_label' => $this->getClubTypeLabel($client),
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Shows the catering calendar to order or remove days
     *
     * @param int $id Client id
     */
    public function ordersAction($id)
    {
        if (!empty($id)) {
            $em = $this->getDoctrine()->getManager();
            $client = $this->getClient($id);
        }

        if (!empty($client)) {

            $orders = $this->prepareOrders($client);
            $menus = $client->getCatering()->getClub()->getLunchTypes();
            asort($menus);

            return $this->render('JCSGYKAdminBundle:Catering:orders_dialog.html.twig', [
                'client' => $client,
                'orders' => $orders,
                'menus'  => $menus,
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Save client orders
     * @param Request $request
     * @param null $id
     */
    public function ordersEditAction(Request $request, $id=null)
    {
        if (!empty($id)) {

            // $em = $this->getDoctrine()->getManager();
            $client = $this->getClient($id);
            $em     = $this->getDoctrine()->getManager();

            // save the ordering data
            if ($request->isMethod('POST')) {

                $orders     = json_decode($request->request->get('orders'), true);
                $new_orders = [];

                if (!empty($orders)) {
                    $new_orders = $this->processOrders($client, $orders);
                }

                if (!empty($new_orders)) {
                    $sec        = $this->get('security.context');
                    $user       = $sec->getToken()->getUser();
                    $company_id = $this->container->get('jcs.ds')->getCompanyId();

                    foreach ($new_orders as $date => $order) {
                        switch ($order['type']) {
                            case 'new':
                                $new_order = new ClientOrder();
                                $new_order->setCompanyId($company_id);
                                $new_order->setClient($client);
                                $new_order->setDate(new \DateTime($date));
                                $new_order->setCreator($user);
                                $new_order->setClosed(false);
                                $new_order->setMenu($client->getCatering()->getMenu());

                                if ($order['value'] == 1) {
                                    $new_order->setOrder(true);
                                    $new_order->setCancel(false);
                                }
                                elseif ($order['value'] == -1) {
                                    $new_order->setOrder(false);
                                    $new_order->setCancel(true);
                                }
                                $em->persist($new_order);

                                break;

                            case 'update':
                                $last_order = $em->getRepository('JCSGYKAdminBundle:ClientOrder')->findOneBy(['date' => new \DateTime($date), 'companyId' => $company_id, 'client' => $client]);

                                // ha közben "eltűnne" a rekord, mi történjen?

                                if ($order['value'] == 1) {
                                    $last_order->setOrder(true);
                                    $last_order->setCancel(false);
                                    $last_order->setClosed(false);
                                }
                                elseif ($order['value'] == -1) {
                                    $last_order->setOrder(false);
                                    $last_order->setCancel(true);
                                    $last_order->setClosed(false);
                                }
                                elseif ($order['value'] == 2) {
                                    $last_order->setOrder(true);
                                    $last_order->setCancel(true);
                                    $last_order->setClosed(false);
                                }
                                elseif ($order['value'] == 3) {
                                    $last_order->setOrder(true);
                                    $last_order->setCancel(false);
                                    $last_order->setClosed(true);
                                }

                                break;

                            case 'remove':
                                $last_order = $em->getRepository('JCSGYKAdminBundle:ClientOrder')->findOneBy(['date' => new \DateTime($date), 'companyId' => $company_id, 'client' => $client]);
                                $em->remove($last_order);

                                break;
                        }

                        // save client modifier
                        $client->setModifier($user);
                        $client->setModifiedAt(new \DateTime());

                        // save
                        $em->flush();
                    }

                    $this->get('session')->getFlashBag()->add('notice', 'Rendelés elmentve');
                }

                // change menus
                $changed_menus = $em->getRepository('JCSGYKAdminBundle:ClientOrder')->changeMenus($client, $request->request->get('order_menu'));
                if ($changed_menus) {
                    $this->get('session')->getFlashBag()->add('notice', 'Rendelés elmentve');
                }

                // if there are no changes, close the window and say bye-bye
                if (empty($new_orders) && empty($changed_menus) ) {
                    $this->get('session')->getFlashBag()->add('notice', 'Nem volt változás a rendelésben.');
                }

                return $this->render('JCSGYKAdminBundle:Catering:orders_dialog.html.twig', [
                    'success' => true,
                ]);
            }

            $orders = $this->prepareOrders($client);

            return $this->render('JCSGYKAdminBundle:Catering:orders_dialog.html.twig', [
                'client' => $client,
                'orders' => $orders
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Merge client orders' changes with current client orders
     */
    private function processOrders(Client $client, $orders)
    {
        $check_dates = $this->getDateCheckParam();
        $first_day_of_period = ( date( 'G' ) < 10 ) ? new \DateTime('tomorrow') : new \DateTime('tomorrow + 1 days');
        // if we don't check dates, the beginning is the start of agreement or 2015-01-01
        if (!$check_dates && !empty($client->getCatering()->getAgreementFrom())) {
            $first_day_of_period = $client->getCatering()->getAgreementFrom();
            $start_of_year = new \DateTime('2015-01-01');
            if ($first_day_of_period < $start_of_year) {
                $first_day_of_period = $start_of_year;
            }
        }

        $last_day_of_period  = new \DateTime('last day of this month + 2 months');
        $catering            = $client->getCatering();
        $days_of_months      = $this->container->get('jcs.ds')->getDaysOfPeriod($first_day_of_period, $last_day_of_period);
        // $monthly_subs        = $this->container->get('jcs.invoice')->getMonthlySubs($catering, $first_day_of_period, $last_day_of_period);
        $changed_days        = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder')->getOrdersForPeriod($client->getId(), $first_day_of_period, $last_day_of_period);
        $new_orders          = [];

        foreach ($days_of_months as $day) {
            $changed_day = 0;
            $closed      = false;
            if (isset($changed_days[$day])) {
                if ($changed_days[$day]->getOrder()) {
                    $changed_day = 1;
                }
                if ($changed_days[$day]->getCancel()) {
                    $changed_day = -1;
                }
                $closed = $changed_days[$day]->getClosed();
                $ordered = $changed_days[$day]->getOrder();
            }
            // $order = (!isset($orders[$day])) ? false : $orders[$day];
            $order = (!isset($orders[$day])) ? 0 : $orders[$day];
            // $sub   = (!isset($monthly_subs[$day])) ? false : true;

            // $new_orders[$day] = $order;

            if ($changed_day == 0) {
                // nincs rekord létrehozva erre a napra
                if ($order == -1) {
                // if ($sub && $order == -1) {
                    // a naptárban nincs kipipálva, a rendelési sablon erre a napra ki van pipálva, és nincs lezárva a rekord
                    $new_orders[$day] = ['type' => 'new', 'value' => -1];
                }
                elseif ($order == 1) {
                // elseif (!$sub && $order == 1) {
                    // a naptárban ki van pipálva, a rendelési sablon erre a napra ki van pipálva, és nincs lezárva a rekord
                    // utánrendelés erre a napra
                    $new_orders[$day] = ['type' => 'new', 'value' => 1];
                }
            }
            else {
                // update esetén nem számít, hogy a sablonban ezen a napon volt-e rendelés vagy sem
                if ($closed) {
                    // lezárt rekordok
                    if ($changed_day === -1 && $order == 1) {
                        // ha van erre a napra rekord létrehozva lemondással, a naptárban ki van pipálva és a rekord le van zárva
                        // utánrendelés erre a napra
                        $new_orders[$day] = ['type' => 'update', 'value' => 1];
                    }
                    elseif ($changed_day === 1 && $order == -1) {
                        // ha van erre a napra rekord létrehozva rendeléssel, a naptárban nincs kipipálva és le van zárva a rekord
                        // rendelés lemondása 110
                        $new_orders[$day] = ['type' => 'update', 'value' => 2];
                    }
                }
                elseif (!$closed) {
                    // rekord nincs lezárva
                    if ($changed_day === -1 && $order == 1 && $ordered == 0) {
                        // ha van erre a napra rekord létrehozva lemondással, a naptárban is ki van pipálva, a rendelési sablon erre a napra ki van pipálva, és nincs lezárva a rekord
                        // rendelés visszaállítása 100
                        $new_orders[$day] = ['type' => 'update', 'value' => 1];
                    }
                    if ($changed_day === -1 && $order == 1 && $ordered == 1) {
                        // ha van erre a napra rekord létrehozva megrendeléssel és lemondással és nincs lezárva a rekord
                        // rendelés visszaállítása lezárva 101
                        $new_orders[$day] = ['type' => 'update', 'value' => 3];
                    }
                    elseif ($changed_day === 1 && $order == -1) {
                        // ha van erre a napra rekord létrehozva rendeléssel, a naptárban nincs kipipálva és nincs lezárva a rekord
                        // lemondás erre a napra 010
                        $new_orders[$day] = ['type' => 'update', 'value' => -1];
                    }
                }
            }
        }

        return $new_orders;
    }

    /**
     * Merge client orders table data with cataring template and holidays
     *
     * @param Client $client
     * @return array
     */
    private function prepareOrders(Client $client)
    {
        $first_day_of_period = new \DateTime('first day of this month - 2 month');
        $last_day_of_period  = new \DateTime('last day of this month + 2 months');
        $catering            = $client->getCatering();
        $menu                = $catering->getMenu();
        $days_of_months      = $this->container->get('jcs.ds')->getDaysOfMonths($first_day_of_period,5);
        $monthly_subs        = $this->container->get('jcs.invoice')->getMonthlySubs($catering, $first_day_of_period, $last_day_of_period);
        $changed_days        = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder')->getOrdersForPeriod($client->getId(), $first_day_of_period, $last_day_of_period);
        $holidays            = $this->container->get('jcs.ds')->getHolidaysDetails($first_day_of_period->format('Y-m-d'), $last_day_of_period->format('Y-m-d'));
        $holyday_type_map    = $this->container->get('jcs.ds')->getHolidayTypeMap();
        $days                = [];

        foreach ($days_of_months as $actual_month => $month) {
            foreach ($month as $day) {
                $new_day = $day;
                $date    = $actual_month . '-' . str_pad($day['day'], 2, '0', STR_PAD_LEFT);
                $class   = [];

                // alapértelmezett érték
                $changed_day = false;
                $closed      = 0;
                // ha van rekord az adott naphoz, akkor változtassuk meg az alapértelmezett false-t
                if (isset($changed_days[$date])) {
                    $menu = $changed_days[$date]->getMenu();
                    if ($changed_days[$date]->getOrder()) {
                        $changed_day = 1;
                    }
                    if ($changed_days[$date]->getCancel()) {
                        $changed_day = -1;
                    }
                    $closed = ($changed_days[$date]->getClosed()) ? 1 : 0;
                }
                else {
                    $menu = $catering->getMenu();
                }
                // állítsuk be az előfizetési sablon alapján a változókat
                if (isset($monthly_subs[$date])) {
                    $sub = true;
                    $new_day['catering'] = 1;
                }
                else {
                    $sub = false;
                    $new_day['catering'] = 0;
                }
                // a hónap napjaihoz állítsuk be a "day" classt és a menü nevét adjuk hozzá
                if (! is_null($day['day'])) {
                    $new_day['menu'] = $menu;
                    $class[]         = 'day';
                }
                else {
                    $class[] = 'empty';
                }
                // ha az adott napra valamilyen munkaszüneti nap tartozik, adjuk hozzá
                if (isset($holidays[$date])) {
                    $new_day['holiday'] = (empty($holidays[$date]['desc'])) ? $holyday_type_map[$holidays[$date]['type']] : $holidays[$date]['desc'];
                }
                else {
                    $new_day['holiday'] = false;
                }
                if ($changed_day !== false) {
                    // ha van rekord az adtott naphoz
                    $new_day['changed'] = ($changed_day == -1) ? -1 : (($closed) ? 1 : '');
                }
                if ($changed_day !== false) {
                    // ha van rekord az adtott naphoz és a rekord le is van zárva
                    $new_day['order'] = ($changed_day == -1) ? 'cancel' : 'order';
                    $class[]          = ($changed_day == -1) ? 'cancel' : 'order';
                }
                // elseif ($changed_day !== false && !$closed) {
                //     // ha van rekord az adtott naphoz és a rekord nincs lezárva
                //     $new_day['order'] = ($changed_day == -1) ? 'none' : 'order';
                // }
                else {
                    // ha nincs rekord, akkor a rendelési sablon alapján állítsuk be az adott napot
                    // if ($sub) {
                        $new_day['order'] = 'none';
                        // $class[]          = 'order';
                    // }
                    // else {
                    //     $new_day['order'] = 'none';
                    // }
                }
                // ha módosítható (+2nap) akkor ezt jelezzük
                if (isset($day['modifiable']) && $day['modifiable']) {
                    $class[] = 'modifiable';
                }
                // ha hétvége az adott nap, akkor az másképpen nézzen ki
                if ($day['weekend']) {
                    $class[] = 'weekend';
                }
                else {
                    $class[] = 'weekday';
                }
                $new_day['closed']                   = $closed;
                $new_day['class']                    = implode(' ', $class);
                $days[$actual_month][$day['week']][] = $new_day;
            }
        }

        return $days;
    }

    /**
     * List of invoices of the Client
     *
     * @param Request $request
     * @param int $id Client id
     */
    public function invoicesAction(Request $request, $id, $invoicetype)
    {
        $em = $this->getDoctrine()->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        if (!empty($id)) {
            $client = $this->getClient($id);
        }

        if (!empty($client)) {
            $types = Invoice::HOMEHELP == $invoicetype ? [Invoice::HOMEHELP] : [Invoice::MONTHLY, Invoice::DAILY];
            // find the last invoices of the client
            $invoices = $em->createQuery("SELECT i FROM JCSGYKAdminBundle:Invoice i WHERE i.companyId = :company_id AND i.client= :client AND i.invoicetype IN (:types) ORDER BY i.createdAt DESC")
                ->setParameter('company_id', $company_id)
                ->setParameter('client', $client)
                ->setParameter('types', $types)
                ->setMaxResults(50)
                ->getResult();

            // create the empty form
            $form_builder = $this->createFormBuilder();
            $field_count = 0;

            foreach ($invoices as $invoice) {
                if ($invoice->isOpen() && empty($invoice->getCancelId())) {
                    $field_count++;
                    $open_amount = $invoice->getAmount() - $invoice->getBalance();

                    $form_builder->add('i' . $invoice->getId(), 'text', [
                        'label' => $open_amount > 0 ? 'Befizetés' : 'Jóváírás',
                        'attr'  => [
                            'class' => 'short',
                        ],
                    ]);
                    if ($open_amount) {
                        $form_builder->add('b' . $invoice->getId(), 'button', [
                            'label' => $ae->formatCurrency($open_amount),
                            'attr'  => [
                                'class' => 'greybutton smallbutton invoice_full_amount',
                                'data-amount' => $open_amount,
                            ]
                        ]);
                    }
                    // no invoice cancelling for homehelp invoices
                    if ($open_amount && $invoice->cancellable() && $invoicetype != Invoice::HOMEHELP) {
                        $form_builder->add('c' . $invoice->getId(), 'button', [
                            'label' => 'sztornózás',
                            'attr'  => [
                                'class' => 'greybutton smallbutton invoice_cancel',
                                'data-id' => $invoice->getId(),
                            ]
                        ]);
                    }
                }
            }
            $route = Invoice::HOMEHELP == $invoicetype ? 'client_homehelp_invoices' : 'client_invoices';
            $form_builder
                ->add('cancel_id', 'hidden')
                // set action depending on the invoice type
                ->setAction($this->generateUrl($route, ['id' => $id]))
                ->setMethod('POST')
            ;

            $form = $form_builder->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                if (empty($data['cancel_id'])) {
                    // normal payments
                    foreach ($invoices as $invoice) {
                        if ($invoice->isOpen()) {
                            $field = 'i' . $invoice->getId();
                            if (!empty($data[$field])) {
                                $res = $invoice->addPayment($data['i' . $invoice->getId()]);
                                if (-1 == $res) {
                                    $form->get($field)->addError(new FormError('Túlfizetés nem lehetséges! Adjon be pontos összeget!'));
                                }
                            }
                            // run the update even if no data was sent
                            $invoice->updateStatus();
                        }
                    }
                } else {
                    // invoice cancel
                    $cancelled_invoice = null;
                    // check for valid invoice id
                    foreach ($invoices as $invoice) {
                        if ($invoice->getId() == $data['cancel_id']) {
                            $cancelled_invocie = $invoice;
                            break;
                        }
                    }
                    if (empty($cancelled_invocie)) {
                        $form->get('cancel_id')->addError(new FormError('Hibás számla azonosító'));
                    } else {
                        $this->container->get('jcs.invoice')->cancelInvoice($invoice);
                    }
                }

                // validate the form again
                if ($form->isValid()) {
                    $em->flush();

                    // update the client global balance too
                    $balance_source = Invoice::HOMEHELP == $invoicetype ? $client->getHomehelp() : $client->getCatering();
                    $this->get('jcs.invoice')->updateBalance($balance_source);

                    if (empty($data['cancel_id'])) {
                        $message = 'Befizetés elmentve';
                    } else {
                        $message = 'Számla szotrnózva';
                    }
                    $this->get('session')->getFlashBag()->add('notice', $message);

                    return $this->render('JCSGYKAdminBundle:Catering:invoices_dialog.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Catering:invoices_dialog.html.twig', [
                'client'        => $client,
                'invoices'      => $invoices,
                'form'          => $form->createView(),
                'field_count'   => $field_count,
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }


    private function saveVisitTask($client, $assignee, $user, $dispatch = null)
    {
        $em = $this->getDoctrine()->getManager();
        $type = is_null($dispatch) ? Task::TYPE_VISIT : Task::TYPE_DISPATCH;

        $task = new Task();
        $task->setAssignee($assignee);
        $task->setCreator($user);
        $task->setClient($client);
        $task->setType($type);
        $task->setDispatch($dispatch);
        $em->persist($task);
        $em->flush();

        // save the stats
        $this->get('jcs.stat')->save(Stat::TYPE_FAMILY_HELP, 1, $assignee->getId());
    }

    /**
     * Edit relatives
     * @param Request $request
     * @param $id
     * @param $relation_id
     * @return Response
     */
    public function relativeEditAction(Request $request, $id, $relation_id)
    {
        $user= $this->get('security.context')->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        $client = $this->getClient($id);

        // security check
        $sec = $this->get('security.context');
        if (!empty($id) && !$client->canEdit($sec)) {
            throw new AccessDeniedHttpException('Access Denied');
        }

        if ($relation_id != 'new') {
            $relation = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id, $relation_id);

            if (empty($relation)) {
                throw new BadRequestHttpException('Invalid relative id');
            }
        }

        if (empty($relation[0])) {
            $relation = new Relation();
            $relation->setChildId($id);
            $new_client = new Client($this->container->get('jcs.ds'));
            $new_client->setCompanyId($company_id);
            $new_client->setType(Client::PARENT);
            $new_client->setCreator($user);
            $new_client->setIsArchived(false);

            // set case admin and numbers
            $new_client->setCaseYear($client->getCaseYear());
            $new_client->setCaseNumber($client->getCaseNumber());
            $new_client->setCaseAdmin($client->getCaseAdmin());
            // set the visible case number
            $new_client->setCaseLabel($this->container->get('jcs.twig.adminextension')->formatCaseNumber($client));

            $relation->setParent($new_client);
        }
        else {
            $relation = $relation[0];
        }

        $form = $this->createForm(new RelativeType($this->container->get('jcs.ds'), $relation->getType(), $client->getType()), $relation->getParent());

        // save
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            // save relation type
            $relation->setType($form->get('relation_type')->getData());
            // set modifier user
            $relation->getParent()->setModifier($user);
            $relation->getParent()->setModifiedAt(new \DateTime());

            if (is_null($relation->getId())) {
                $em->persist($relation->getParent());
                $em->persist($relation);
            }

            if ($relation->getId()) {
                // get the related clients
                $relative = $relation->getParent();

                // if its a mother, we must update any related client record too
                if ($relation->getType() == Relation::MOTHER) {
                    $siblings = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getChildren($relative);

                    // if the birthname is set, then we use that, otherwise the name
                    if ($relative->getBirthFirstname() || $relative->getBirthLastname()) {
                        $mother_title = $relative->getBirthTitle();
                        $mother_firstname = $relative->getBirthFirstname();
                        $mother_lastname = $relative->getBirthLastname();
                    } else {
                        $mother_title = $relative->getTitle();
                        $mother_firstname = $relative->getFirstname();
                        $mother_lastname = $relative->getLastname();
                    }

                    // update the mothers name fields
                    foreach ($siblings as $sibling_rel) {
                        $sibling = $this->getClient($sibling_rel->getChildId());
                        $sibling->setMotherTitle($mother_title);
                        $sibling->setMotherFirstname($mother_firstname);
                        $sibling->setMotherLastname($mother_lastname);
                    }
                } // or if save_to_all is checked, we must copy the relative's data to all other sibling
                elseif (!empty($form['save_to_all']) && !empty($form['save_to_all']->getData())) {
                    // find all clients of this case
                    $case = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getCase($client);
                    foreach ($case as $copy_client) {
                        // only work with the remaining siblings
                        if ($copy_client->getId() != $client->getId()) {
                            // update or create the relation
                            $this->copyRelations($client, $copy_client, $relation);
                        }
                    }
                }
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Hozzátartozó elmentve');

            return $this->render('JCSGYKAdminBundle:Dialog:relative.html.twig', [
                'success' => true
            ]);
        }

        return $this->render('JCSGYKAdminBundle:Dialog:relative.html.twig', [
            'form'     => $form->createView(),
            'relative' => $relation,
        ]);
    }

    /**
     * Delete Relatives
     * @param Request $request
     * @param $id
     * @param $relation_id
     * @return Response
     */
    public function relativeDeleteAction(Request $request, $id, $relation_id)
    {
        $user= $this->get('security.context')->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        $client = $this->getClient($id);
        $relation = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id, $relation_id);

        if (!empty($relation[0])) {
            $relation = $relation[0];
        }

        if (empty($relation)) {
            throw new BadRequestHttpException('Invalid relative id');
        }
        // security check
        $sec = $this->get('security.context');
        if (!empty($id) && !$client->canEdit($sec)) {
            throw new AccessDeniedHttpException('Access Denied');
        }

        $form = $this->createFormBuilder()
            ->add('delete', 'hidden', [
                'data' => true
            ])
            ->getForm();


        // save
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data = $form->getData();
            if (!empty($data['delete'])) {
                $parent = $relation->getParent();

                // see if this relative has any other child record
                $children = $em->createQuery("SELECT r FROM JCSGYKAdminBundle:Relation r WHERE r.parent = :parent AND r.childId != :child")
                    ->setParameter('parent', $parent)
                    ->setParameter('child', $client->getId())
                    ->getResult();

                // no more children, we can remove the parent record too
                if (empty($children)) {
                    $em->remove($parent);
                }
                $em->remove($relation);
                $em->flush();

                $this->get('session')->getFlashBag()->add('notice', 'Hozzátartozó törölve');
            }

            return $this->render('JCSGYKAdminBundle:Dialog:relative_delete.html.twig', [
                'success' => true
            ]);
        }

        return $this->render('JCSGYKAdminBundle:Dialog:relative_delete.html.twig', [
            'form' => $form->createView(),
            'relation' => $relation,
        ]);
    }

    /**
     * Edits the client data
     *
     * @Secure(roles="ROLE_USER")
     * @param Request $request
     * @param null $id
     * @param null $client_type
     */
    public function editAction(Request $request, $id = null, $client_type=null)
    {

        // TODO: utca adatbázis + ellenőrzés

        $client = null;
        $em = $this->getDoctrine()->getManager();
        $co = $this->container->get('jcs.ds')->getCompany();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();

        if (!empty($id)) {
            // get the client data
            $client = $this->getClient($id);
        }
        else {
            // new client
            $client = new Client();

            // family help and child welfare users get the case admin set automatically
            if ($sec->isGranted('ROLE_FAMILY_HELP') || $sec->isGranted('ROLE_CHILD_WELFARE') || $sec->isGranted('ROLE_CATERING')) {
                $client->setCaseAdmin($user);
            }
            if (empty($client_type)) {
                throw new BadRequestHttpException('Invalid client type');
            }
            $client->setType($client_type);
            $client->setCompanyId($company_id);
        }

        if (!empty($client)) {
            if (empty($client_type)) {
                $client_type = $client->getType();
            }
            // Global security check for client type
            $this->get('jcs.ds')->userRoleCheck($client->getType());

            $sec = $this->get('security.context');
            // see if this user is allowed to edit - if not we simply redirect her to the view page
            if (!empty($id) && !$client->canEdit($sec)) {
                return $this->redirect($this->generateUrl('client_view', ['id' => $id]));
            }

            // get the relatives for CHILD WELFARE or CATERING
            $relatives = [];
            $relation_types = [];
            if ($client->getId() && in_array($client->getType(), [Client::CW, Client::CA])) {
                $relatives = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id);
                $relation_types = $this->container->get('jcs.ds')->getRelationTypes($client->getType());
                foreach ($relatives as $relative) {
                    unset($relation_types[$relative->getType()]);
                }
            }

            $form = $this->createForm(new ClientType($this->container->get('jcs.ds'), $client), $client);

            $orig_year = $client->getCaseYear();
            $orig_casenum = $client->getCaseNumber();

            // save the client
            $form->handleRequest($request);

            if ($form->isValid()) {
                // set modifier user
                $client->setModifier($user);
                $client->setModifiedAt(new \DateTime());
                $case_num = $client->getCaseNumber();

                // save the new client data
                if (is_null($client->getId())) {
                    if (empty($case_num)) {
                        // if not defined, get the next case number from the ClientSequence Service
                        // also checks for year changes and resets the sequence for a new year if needed
                        $nextVal = $this->get('jcs.seq')->nextVal($co, $client->getType());
                        if (false === $nextVal) {
                            // this is really bad
                            throw new HttpException(500);
                        }

                        $client->setCaseYear($nextVal['year']);
                        $client->setCaseNumber($nextVal['id']);
                    } else {
                        $copy_case = true;
                    }

                    // set the creator
                    $client->setCreator($user);
                    $client->setCompanyId($company_id);
                    $client->setIsArchived(false);

                    // set the client type
                    $client->setType($client_type);

                    $em->persist($client);
                    $em->flush();

                    // turn off the logging for the rest of the process
                    $this->container->get('history.logger')->off();

                    // if case number given, we must copy over a few fields from that case
                    if (!empty($copy_case)) {
                        $this->copyCaseData($client);
                    }
                } // restore the case number and year
                elseif (empty($case_num)) {
                    $client->setCaseYear($orig_year);
                    $client->setCaseNumber($orig_casenum);
                }

                // set the visible case number
                $client->setCaseLabel($this->container->get('jcs.twig.adminextension')->formatCaseNumber($client));

                // handle/save the utilityproviders
                foreach ($client->getUtilityprovidernumbers() as $up) {
                    $val = $up->getValue();
                    if (empty($val)) {
                        // remove the empty providers
                        $client->removeUtilityprovidernumber($up);
                        $em->remove($up);
                    } else {
                        // set the client id
                        $up->setClient($client);
                        // save the rest
                        $em->persist($up);
                    }
                }

                // Do we need this?
                //$em->flush();

                // handle/save the addresses
                foreach ($client->getAddresses() as $adr) {
                    $val = $adr->getCity() . $adr->getStreet();
                    if (empty($val)) {
                        // remove the empty address
                        $client->removeAddress($adr);
                        $em->remove($adr);
                    } else {
                        $aid = $adr->getId();
                        if (empty($aid)) {
                            // set the client id
                            $adr->setClient($client);
                            $adr->setCreator($user);
                            $em->persist($adr);
                        } else {
                            $adr->setModifier($user);
                        }
                    }
                }

                // copy the mothers name from the relatives record
                $mother_relation = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelationByType($client->getId(), Relation::MOTHER);
                if (!empty($mother_relation)) {
                    $mother = $mother_relation->getParent();
                    // if the birthname is set, then we use that, otherwise the name
                    if ($mother->getBirthFirstname() || $mother->getBirthLastname()) {
                        $client->setMotherFirstname($mother->getBirthFirstname());
                        $client->setMotherLastname($mother->getBirthLastname());
                    } else {
                        $client->setMotherFirstname($mother->getFirstname());
                        $client->setMotherLastname($mother->getLastname());
                    }
                    $client->setMotherTitle($mother->getTitle());
                }

                // save the parameters
                $pgroups = $this->container->get('jcs.ds')->getParamGroup(1, false, $client->getType());
                $param_data = [];
                foreach ($pgroups as $param) {
                    $param_data[$param->getId()] = $form->get('param_' . $param->getId())->getData();
                }
                $client->setParams($param_data);

                $em->flush();

                if (empty($id) && in_array($client->getType(), [Client::FH, Client::CW])) {
                    // create a new visit task for the new client
                    // only for family help or child welfare clients
                    $this->saveVisitTask($client, $client->getCaseAdmin(), $user);
                }

                $this->get('session')->getFlashBag()->add('notice', 'Ügyfél elmentve');

                return $this->redirect($this->generateUrl('client_view', ['id' => $client->getId()]));
            }

            // get the recommended fields
            $all_rec_fields = $this->container->get('jcs.ds')->getOption('recommended_fields');
            $rec_fields = isset($all_rec_fields[$client_type]) ? $all_rec_fields[$client_type] : [];

            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);

            return $this->render('JCSGYKAdminBundle:Client:edit.html.twig', [
                'client'             => $client,
                'problems'           => $problems,
                'form'               => $form->createView(),
                'relatives'          => $relatives,
                'new_relations'      => $relation_types,
                'client_type'        => $client_type,
                'recommended_fields' => json_encode($rec_fields),
                'logs'               => $this->container->get('history.logger')->getLogs($client),
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Copies over a few fields from the given case
     * called when new client is saved
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @return bool
     */
    private function copyCaseData(Client &$client)
    {
        if ($client->getCaseNumber() && $client->getCaseYear()) {
            $em = $this->getDoctrine()->getManager();
            // find the case
            $case = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getCase($client);

            if (!empty($case[0])) {
                $sibling = $case[0];

                // case admin
                $client->setCaseAdmin($sibling->getCaseAdmin());

                // mothers data
                $this->copyFields($sibling, $client, ['MotherTitle', 'MotherFirstname', 'MotherLastname']);

                // save the location data if empty
                if (!$client->getCity() || !$client->getStreet()) {
                    $this->copyFields($sibling, $client, ['Country', 'ZipCode', 'City', 'Street', 'StreetType', 'StreetNumber', 'FlatNumber']);
                }
                if (!$client->getLocationCity() || !$client->getLocationStreet()) {
                    $this->copyFields($sibling, $client, ['LocationCountry', 'LocationZipCode', 'LocationCity', 'LocationStreet', 'LocationStreetType', 'LocationStreetNumber', 'LocationFlatNumber']);
                }
                // clone the last address of the case
                $addresses = $sibling->getAddresses();
                $act_addr = $addresses->last();
                if (!empty($act_addr)) {
                    $new_addr = clone $act_addr;
                    $new_addr->setClient($client);
                    $em->persist($new_addr);
                }

                // copy all the relations
                $this->copyRelations($sibling, $client);

                $em->flush();

                return true;
            }

            return false;
        }
    }

    /**
     * Copy the relations from a client to another
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $from
     * @param \JCSGYK\AdminBundle\Entity\Client $to
     * @param \JCSGYK\AdminBundle\Entity\Relation $relation optional
     */
    private function copyRelations(Client $from, Client $to, Relation $relation = null)
    {
        $em = $this->getDoctrine()->getManager();

        // find all the relations of this client if not provided
        if (empty($relation)) {
            $rels = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($from->getId());
        }
        else {
            $rels = [$relation];
        }

        foreach ($rels as $rel) {
            // check if the new client has a relation of this type
            $existing_relation = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelationByType($to->getId(), $rel->getType());

            // only create a new relation if necessary
            if (empty($existing_relation)) {
                $relative = new Relation();
                $relative->setType($rel->getType());
                $relative->setChildId($to->getId());

                if ($rel->getType() == Relation::MOTHER) {
                    // we only link to the mother,
                    $relative->setParent($rel->getParent());
                }
                else {
                    // but clone the other relations
                    $from_parent = $rel->getParent();
                    $new_parent = clone $from_parent;
                    $em->persist($new_parent);
                    $relative->setParent($new_parent);
                }
                $em->persist($relative);
            }
            // if there is an old relation of this type, then overwrite the relatives data
            else {
                $from_parent = $rel->getParent();
                $to_parent = $existing_relation->getParent();

                $this->copyFields($from_parent, $to_parent, ['Title', 'Firstname', 'Lastname', 'Gender', 'BirthFirstname', 'BirthLastname', 'BirthPlace', 'BirthDate']);
                $this->copyFields($from_parent, $to_parent, ['MotherTitle', 'MotherFirstname', 'MotherLastname', 'Citizenship', 'CitizenshipStatus']);
                $this->copyFields($from_parent, $to_parent, ['Phone', 'Mobile', 'Fax', 'Email', 'CitizenshipStatus', 'Note']);
                $this->copyFields($from_parent, $to_parent, ['Country', 'ZipCode', 'City', 'Street', 'StreetType', 'StreetNumber', 'FlatNumber']);
                $this->copyFields($from_parent, $to_parent, ['LocationCountry', 'LocationZipCode', 'LocationCity', 'LocationStreet', 'LocationStreetType', 'LocationStreetNumber', 'LocationFlatNumber']);
            }
        }  // end foreach
    }

    /**
     * Copy a listo of fields from one client to an other
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $to_client
     * @param \JCSGYK\AdminBundle\Entity\Client $from_client
     * @param array $fields
     */
    private function copyFields(Client $from_client, Client $to_client, array $fields)
    {
        foreach ($fields as $field) {
            $setter = 'set' . $field;
            $getter = 'get' . $field;
            $to_client->$setter($from_client->$getter());
        }
    }

    /**
     * Archive clients
     *
     * @PreAuthorize("hasRole('ROLE_ADMIN') or hasRole('ROLE_ASSISTANCE') or hasRole('ROLE_CATERING')")
     * @param Request $request
     * @param $id
     */
    public function archiveAction(Request $request, $id)
    {
        if (!empty($id)) {
            // get the client
            $client = $this->getClient($id);
            if (empty($client)) {
                throw new BadRequestHttpException('Invalid client id');
            }

            // check for any open problems, only arhivable if no problems are open
            $open_problems = $this->checkClientForOpenProblems($client);
            // for catering clients, check if an active catering or homehelp service exists
            $cat_active = $this->checkClientForActiveCatering($client);
            $hh_active = $this->checkClientForActiveHomeHelp($client);

            if ($open_problems > 0 || $cat_active || $hh_active) {
                // can't archive, show the popup

                return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                    'client'          => $client,
                    'open_problems'   => $open_problems,
                    'catering_active' => $cat_active,
                    'homehelp_active' => $hh_active,
                ]);
            }

            $archive = new Archive;
            $form = $this->createForm(new ArchiveType($this->container->get('jcs.ds'), $client->getIsArchived()), $archive);

            // save
            $form->handleRequest($request);

            if ($form->isValid()) {
                $operation = $form->get('operation')->getData();
                $em = $this->getDoctrine()->getManager();
                $user = $this->get('security.context')->getToken()->getUser();
                $archive->setClient($client)
                    ->setCreator($user)
                    ->setCreatedAt(new \DateTime())
                ;

                $em->persist($archive);

                // archive the client
                $client->setIsArchived(1 - $operation);

                $em->flush();

                $this->get('session')->getFlashBag()->add('notice', 'Ügyfél elmentve');

                return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                    'success' => true,
                ]);
            }

            return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                'client'          => $client,
                'form'            => $form->createView(),
                'open_problems'   => $open_problems,
                'catering_active' => $cat_active,
                'homehelp_active' => $hh_active,
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    private function checkClientForOpenProblems(Client $client)
    {
        // only check active clients!
        if ($client->getIsArchived() == 1) {
            return 0;
        }

        $open_problems = 0;
        // get only the undeleted problems
        $problems      = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($client->getId());

        foreach ($problems as $problem) {
            if ($problem->getIsActive()) {
                $open_problems++;
            }
        }

        return $open_problems;
    }

    private function checkClientForActiveCatering(Client $client)
    {
        // only check active clients!
        if ($client->getIsArchived() == 1) {
            return false;
        }

        $active_catering = false;
        if (Client::CA == $client->getType()) {
            $catering = $client->getCatering();
            if ($catering && $catering->hasAgreement()) {
                $active_catering = true;
            }
        }

        return $active_catering;
    }

    private function checkClientForActiveHomeHelp(Client $client)
    {
        // only check active clients!
        if ($client->getIsArchived() == 1) {
            return false;
        }

        $active_homehelp = false;
        if (Client::CA == $client->getType()) {
            $homehelp = $client->getHomehelp();
            if ($homehelp && $homehelp->hasAgreement()) {
                $active_homehelp = true;
            }
        }

        return $active_homehelp;
    }

    /**
     * View client details
     *
     * @Secure(roles="ROLE_USER")
     */
    public function viewAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $problems = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);
        }
        if (!empty($client)) {
            // Global security check for client type
            $this->get('jcs.ds')->userRoleCheck($client->getType());

            $sec = $this->get('security.context');
            $client_types = $this->container->get('jcs.ds')->getClientTypes();

            // get the relatives only for CHILD WELFARE or CATERING
            $relatives = [];
            $relation_types = [];
            if (in_array($client->getType(), [Client::CW, Client::CA])) {
                $relatives = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:Client')->getRelations($id);
                $relation_types = $this->container->get('jcs.ds')->getRelationTypes();
                foreach ($relatives as $relative) {
                    if (isset($relation_types[$relative->getType()])) {
                        unset($relation_types[$relative->getType()]);
                    }
                }
            }

            // check the open orders for manual invoice alert
            if (Client::CA == $client->getType()) {
                $invoice_required = $this->container->get('doctrine')->getRepository('JCSGYKAdminBundle:ClientOrder')->checkForOpenOrders($client);
            }

            return $this->render('JCSGYKAdminBundle:Client:view.html.twig', [
                'client'           => $client,
                'problems'         => $problems,
                'can_edit'         => $client->canEdit($sec),
                'display_type'     => count($client_types) > 1, // only display the client type if there are more then one types of this company
                'relatives'        => $relatives,
                'new_relations'    => $relation_types,
                'client_type'      => $client->getType(),
                'logs'             => $this->container->get('history.logger')->getLogs($client),
                'club_type'        => $this->getClubtype($client),
                'club_type_label'  => $this->getClubTypeLabel($client),
                'invoice_required' => !empty($invoice_required),
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    private function getClubTypeLabel(Client $client)
    {
        $club_type = $this->getClubtype($client);

        return HomeHelp::HELP == $club_type ? 'gondozás' : 'látogatás';
    }

    private function getClubtype(Client $client)
    {
        // get the users club-type for catering clients
        $club_type = HomeHelp::VISIT;
        if (Client::CA == $client->getType()) {
            // check catering record
            $catering = $client->getCatering();
            if (!empty($catering)) {
                $club = $catering->getClub();
                if (!empty($club)) {

                    return $club->getHomehelptype();
                }
            }

            $homehelp = $client->getHomehelp();
            if (!empty($homehelp)) {
                $club = $homehelp->getClub();
                if (!empty($club)) {

                    return $club->getHomehelptype();
                }
            }
        }

        return $club_type;
    }

    /**
     * Get only the problem list of the client.
     * Used with the refreshProblems action
     *
     * @Secure(roles="ROLE_USER")
     */
    public function problemsAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);
            $sec = $this->get('security.context');

            return $this->render('JCSGYKAdminBundle:Client:_problems.html.twig', ['client' => $client, 'problems' => $problems, 'can_edit' => $client->canEdit($sec)]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Get only the list of relatives for the client.
     * Used with the reloadRelatives JS action
     *
     * @Secure(roles="ROLE_USER")
     */
    public function relativesAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $relatives = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id);
            $relation_types = $this->container->get('jcs.ds')->getRelationTypes();
            foreach ($relatives as $relative) {
                if (isset($relation_types[$relative->getType()])) {
                    unset($relation_types[$relative->getType()]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Client:_relatives.html.twig', [
                'client' => $client,
                'relatives' => $relatives,
                'new_relations' => $relation_types,
                'edit' => true
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Get the client data
     * @param int $id client id
     * @return Client
     */
    protected function getClient($id)
    {
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
            ->findOneBy(['id' => $id, 'companyId' => $company_id]);
    }

    /**
     * Try to decide if a sting is a case number
     * @param text $q
     */
    protected function isCase($q, $client_type)
    {
        $company = $this->container->get('jcs.ds')->getCompany();
        $tpl = $company['caseNumberTemplate'][$client_type];
        preg_match_all('/(.*?)(\{.*?\})/', $tpl, $matches, PREG_SET_ORDER);

        $pattern = '';
        foreach ($matches as $m) {
            $pattern .= preg_quote($m[1], '/');
            $pattern .= $m[2] == '{year}' ? '\d{4}' : '\d?';
        }
        preg_match("/(*UTF8){$pattern}/i", $q, $case_matches);

        return !empty($case_matches[0]);
    }

    /**
     * Client search
     * @param Request $request
     * @param $client_type
     * @internal param string $q search string
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Secure(roles="ROLE_USER")
     */
    public function searchAction(Request $request, $client_type)
    {
        if (empty($client_type)) {
            throw new BadRequestHttpException('Invalid client type');
        }
        $q = $request->query->get('q');

        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $limit = 100;

        $re = [];
        $sql = '';

        // save the search string
        $this->get('session')->set('quicksearch.' . $client_type, $q);

        $time_start = microtime(true);

        $db = $this->get('doctrine.dbal.default_connection');
        if (empty($q)) {
            $sec = $this->get('security.context');
            $user= $sec->getToken()->getUser();
            $ds = $this->container->get('jcs.ds');

            // list all the active clients owned by this user for the catering users
            if ($sec->isGranted('ROLE_CATERING')) {
                $clubs = $ds->getMyClubs();
                $club_list = [];
                foreach ($clubs as $club) {
                    $club_list[] = $club->getId();
                }
                $club_list = implode(',', $club_list);
                if (!empty($clubs)) {
                    $sql = "SELECT c.id, c.type, c.case_year, c.case_number, c.case_label, c.company_id, c.title, c.firstname, c.lastname, c.mother_firstname, c.mother_lastname, c.zip_code, c.city, c.street, c.street_type, c.street_number, c.flat_number, c.is_archived "
                        . " FROM client c LEFT JOIN catering a ON c.id = a.client_id LEFT JOIN home_help h ON c.id=h.client_id WHERE"
                        . " (a.club_id IN ($club_list) OR h.club_id IN ($club_list)) AND c.is_archived = 0 AND c.type = {$client_type}"
                        . " ORDER BY c.lastname, c.firstname LIMIT " . $limit;
                    $re = $db->fetchAll($sql);
                }
            }
            // list all the active clients owned by this user for the FH or CW users
            elseif ($sec->isGranted('ROLE_FAMILY_HELP') || $sec->isGranted('ROLE_CHILD_WELFARE')) {
                $user_id = $user->getId();
                $sql = "SELECT id, type, case_year, case_number, case_label, company_id, title, firstname, lastname, mother_firstname, mother_lastname, zip_code, city, street, street_type, street_number, flat_number, is_archived FROM client WHERE"
                    . " case_admin = {$user_id} AND is_archived = 0 AND type = {$client_type}"
                    . " ORDER BY lastname, firstname LIMIT " . $limit;
                $re = $db->fetchAll($sql);
            }
        }
        else {
            $num_ver = Client::cleanupNum($q);
            $sql = "SELECT id, type, case_year, case_number, case_label, company_id, title, firstname, lastname, mother_firstname, mother_lastname, zip_code, city, street, street_type, street_number, flat_number, is_archived FROM client WHERE";
            // recognize a case number
            if ($this->isCase($q, $client_type)) {
                $sql .= " case_label LIKE {$db->quote($q . '%')} AND company_id={$db->quote($company_id)} AND type={$client_type}";
                $sql .= " ORDER BY case_year, LENGTH(case_number), case_number, lastname, firstname LIMIT " . $limit;
            }
            // search for ID
            elseif (is_numeric($num_ver)) {
                $sql .= " (case_number={$db->quote($num_ver)} AND company_id={$db->quote($company_id)} AND type={$client_type}) OR (social_security_number LIKE {$db->quote($num_ver . '%')} AND company_id={$db->quote($company_id)} AND type={$client_type})";
                $sql .= " ORDER BY lastname, firstname LIMIT " . $limit;
            }
            else {
                $search_words = explode(' ', trim($q));
                // We cant use FULLTEXT search for fields with very light weights (same values most of the times)
                // because the indexer ignores these. Street number and street types are such fields.
                // We must use HAVING after the FULLTEXT search to filter these fields.
                $first = reset($search_words);
                // if the first word is a number, then we use it as a zip code
                if (is_numeric($first)){
                    array_shift($search_words);
                    $first = $db->quote($first);
                }
                else {
                    $first = false;
                }
                $last = end($search_words);
                // if the last word is a number, we use that for the street number search
                if (preg_match('/^\d+(\/|\.|-)?\w*\.?\*?$/', $last)) {
                    // remove the last element
                    array_pop($search_words);
                    // also remove any extra chars
                    $last = strtr($last, ['/' => '', '.' => '', ' ' => '', '*' => '%']);
                    //$last .= '%';
                    $last = $db->quote($last);
                }
                else {
                    $last = false;
                }
                // check for street types
                $street_types = [];
                // TODO: we need to find a good location for this stree type list:
                $stype_list = [ 'akna', 'alsó', 'alsósor', 'állomás', 'árok', 'átjáró', 'bányatelep', 'bástya', 'bástyája', 'csónakházak', 'domb', 'dűlő', 'dűlőút', 'emlékpark', 'erdészház', 'erdő', 'erdősor', 'fasor', 'fasora', 'felső', 'felsősor', 'forduló', 'főtér', 'gát', 'gyümölcsös', 'határsor', 'határút', 'hegy', 'iskola', 'kapu', 'kert', 'kertek', 'kolónia', 'körönd', 'körtér', 'körút', 'körútja', 'köz', 'középsor', 'kültelek', 'lakópark', 'lejáró', 'lejtő', 'lépcső', 'lépcsősor', 'liget', 'major', 'MÁV pályaudvar', 'menedékház', 'mélyút', 'oldal', 'őrház', 'őrházak', 'park', 'parkja', 'part', 'pályaudvar', 'puszta', 'rakpart', 'rét', 'sétaút', 'sétány', 'sor', 'sportpálya', 'sugárút', 'szőlőhegy', 'tag', 'tanya', 'tanyák', 'telep', 'tere', 'tető', 'tér', 'turistaház', 'udvar', 'utca', 'utcája', 'út', 'útja', 'üdülőpart', 'vadászház', 'vasútállomás', 'vár', 'vízmű', 'víztároló', 'völgy', 'zártkert', 'zug'];
                $stype_shorts = ['u' => 'utca', 'u.' => 'utca', 'krt' => 'körút', 'krt.' => 'körút'];
                foreach ($search_words as $sk => $sw) {
                    if (in_array($sw, $stype_list)) {
                        $street_types[] = $db->quote($sw);
                        unset($search_words[$sk]);
                        continue;
                    }
                    // check for street type short versions
                    if (isset($stype_shorts[$sw])) {
                        $street_types[] = $db->quote($stype_shorts[$sw]);
                        unset($search_words[$sk]);
                    }
                }

                $qr = $db->quote('+' . implode('* +', $search_words) . '*');

                $sql .= " MATCH (firstname, lastname, street, mother_firstname, mother_lastname) AGAINST ({$qr} IN BOOLEAN MODE)";

                $xsql = ['company_id=' . $company_id, "type={$client_type}"];

                // if we search for street number
                if (!empty($first)) {
                    $xsql[] = "zip_code LIKE " . $first;
                }
                if (!empty($last)) {
                    $xsql[] = "street_number LIKE " . $last;
                }
                if (!empty($street_types)) {
                    $xsql[] = "street_type IN (" . implode(',', $street_types) . ")";
                }

                $sql .= " HAVING " . implode(' AND ', $xsql);
                $sql .= " ORDER BY lastname, firstname LIMIT " . $limit;
            }
            $re = $db->fetchAll($sql);
        }
        $time_end = microtime(true);
        $time = number_format(($time_end - $time_start) * 1000, 3, ',', ' ');

        return $this->render('JCSGYKAdminBundle:Client:results.html.twig', ['clients' => $re, 'time' => $time, 'sql' => $sql, 'resnum' => count($re)]);
    }

    /**
     * Client Catering Manual Invoice creator
     *
     * @param Request $request
     * @param int $id Client id
     * @return Response
     *
     * @Security("has_role('ROLE_CATERING')")
     * @Route("/clients/create-invoice/{id}", name="create_invoice")
     */
    public function createInvoiceAction(Request $request, $id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
        }
        if (empty($client)) {
            throw new BadRequestHttpException('Invalid client id');
        }
        $closing_type = MonthlyClosing::DAILY;
        $invoice_service = $this->container->get('jcs.invoice');

        // get global parameter for catering date check
        $check_dates = $this->getDateCheckParam();
        if ($check_dates) {
            // we enforce that catering orders can only be in the future
            if (date('H') < 10) {
                // before 10:00 we can change the next day
                $start = new \DateTime('+1 day');
            } else {
                // after 10:00 we can only change the deay after tomorrow
                $start = new \DateTime('+2 day');
            }
        } else {
            // we are allowing the creation of past date orders
            if (!empty($client->getCatering()->getAgreementFrom())) {
                $start = $client->getCatering()->getAgreementFrom();
                $start_of_year = new \DateTime('2015-01-01');
                if ($start < $start_of_year) {
                    $start = $start_of_year;
                }
            }
        }

        if (date('j') < 25) {
            $end = new \DateTime('last day of this month');
        } else {
            $end = new \DateTime('last day of next month');
        }

        // if some settings are missing, it is possible that we can not create an invoice
        if (!empty($start)) {
            $invoice = $invoice_service->create($client, clone $start, clone $end, $closing_type);
            $invoice_service->updateBalance($client->getCatering());
        }

        $result = !empty($invoice) ? 1 : 0;

        return new Response($result, Response::HTTP_OK);
    }

    /**
     * Reads the Date Check setting from /app/config/parameters.yml if available
     * @return bool
     */
    private function getDateCheckParam() {
        $check_dates = true;
        if ($this->container->hasParameter('catering_date_check')) {
            $check_dates = $this->container->getParameter('catering_date_check');
        }

        return $check_dates;
    }
}
