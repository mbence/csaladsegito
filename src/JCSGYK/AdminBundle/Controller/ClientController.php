<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
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
     */
    public function visitAction($id = null)
    {
        // find the users. We need the case admin first, then the assignees of the problems, and then everyone else
        // only active users will be displayed
        $request = $this->getRequest();

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
     * Edit catering fields
     *
     * @Secure(roles="ROLE_USER")
     */
    public function cateringEditAction($id = null)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            $em = $this->getDoctrine()->getManager();
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            $ae = $this->container->get('jcs.twig.adminextension');
            $ds = $this->container->get('jcs.ds');
            $sec = $this->get('security.context');
            $user= $sec->getToken()->getUser();

            // get the client
            $client = $this->getClient($id);

            // Global security check for user type
            $ds->userRoleCheck($client->getType());

            $form = $this->createForm(new CateringType($ds), $client->getCatering());

            // save the catering data
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {

                    // save
                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Étkeztetés elmentve');

                    return $this->render('JCSGYKAdminBundle:Catering:catering_dialog.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Catering:catering_dialog.html.twig', [
                'client' => $client,
                'form' => $form->createView(),
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
     * Save client orders
     */
    public function ordersEditAction($id=null)
    {
        $request = $this->getRequest();

        if (!empty($id)) {

            // $em = $this->getDoctrine()->getManager();
            $client = $this->getClient($id);

            // save the ordering data
            if ($request->isMethod('POST')) {

                $orders     = json_decode($request->request->get('orders'), true);
                $new_orders = [];

                if (!empty($orders)) {
                    $new_orders = $this->processOrders($client, $orders);
                }

                // return $this->render('JCSGYKAdminBundle:Catering:orders_dialog.html.twig', [
                //     'success'    => true,
                //     'new_orders' => $new_orders,
                //     'orders'     => $orders
                // ]);

                if (empty($new_orders)) {
                    // if there are no changes, close the window and say bye-bye
                    $this->get('session')->getFlashBag()->add('notice', 'Nem volt változás a rendelésben.');
                }
                else {

                    $em         = $this->getDoctrine()->getManager();
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

                                    // $em->persist($last_order);
                                }
                                elseif ($order['value'] == -1) {
                                    $last_order->setOrder(false);
                                    $last_order->setCancel(true);
                                    $last_order->setClosed(false);

                                    // $em->persist($last_order);
                                }
                                elseif ($order['value'] == 2) {
                                    $last_order->setOrder(true);
                                    $last_order->setCancel(true);
                                    $last_order->setClosed(false);

                                    // $em->persist($last_order);
                                }
                                // elseif ($order['value'] == 0) {
                                //     $last_order->setOrder(false);
                                //     $last_order->setCancel(false);
                                //     $last_order->setClosed(false);
                                // }

                                break;

                            case 'remove':
                                $last_order = $em->getRepository('JCSGYKAdminBundle:ClientOrder')->findOneBy(['date' => new \DateTime($date), 'companyId' => $company_id, 'client' => $client]);
                                $em->remove($last_order);

                                break;
                        }

                        // save
                        $em->flush();
                    }

                    $this->get('session')->getFlashBag()->add('notice', 'Rendelés elmentve');
                }

                return $this->render('JCSGYKAdminBundle:Catering:orders_dialog.html.twig', [
                    'success' => true,
                    // 'orders'  => $error
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
        $first_day_of_period = new \DateTime('tomorrow + 1 days');
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
                        // ha van erre a napra rekord létrehozva utánrendeléssel, a naptárban nincs kipipálva és le van zárva a rekord
                        // rekord nullázása
                        $new_orders[$day] = ['type' => 'update', 'value' => 2];
                    }
                }
                elseif (!$closed) {
                    // rekord nincs lezárva
                    if ($changed_day === -1 && $order == 1) {
                        // ha van erre a napra rekord létrehozva lemondással, a naptárban is ki van pipálva, a rendelési sablon erre a napra ki van pipálva, és nincs lezárva a rekord
                        // rekord nullázása
                        $new_orders[$day] = ['type' => 'update', 'value' => 1];
                    }
                    elseif ($changed_day === 1 && $order == -1) {
                        // ha van erre a napra rekord létrehozva utánrendeléssel, a naptárban nincs kipipálva, a rendelési sablon erre a napra nincs kipipálva, és nincs lezárva a rekord
                        // utánrendelés erre a napra
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
                    if ($changed_days[$date]->getOrder()) {
                        $changed_day = 1;
                    }
                    if ($changed_days[$date]->getCancel()) {
                        $changed_day = -1;
                    }
                    $closed = ($changed_days[$date]->getClosed()) ? 1 : 0;
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
     * @param int $id Client id
     */
    public function invoicesAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $request = $this->getRequest();

        if (!empty($id)) {
            $client = $this->getClient($id);
        }

        if (!empty($client)) {
            // find the last invoices of the client
            $invoices = $em->createQuery("SELECT i FROM JCSGYKAdminBundle:Invoice i WHERE i.companyId = :company_id AND i.client= :client ORDER BY i.createdAt DESC")
                ->setParameter('company_id', $company_id)
                ->setParameter('client', $client)
                ->setMaxResults(20)
                ->getResult();

            // create the empty form
            $form_builder = $this->createFormBuilder();
            $field_count = 0;

            foreach ($invoices as $invoice) {
                if (Invoice::OPEN == $invoice->getStatus()) {
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
                }
            }

            $form = $form_builder->getForm();

            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    foreach ($invoices as $invoice) {
                        if (Invoice::OPEN == $invoice->getStatus()) {
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

                    // validate the form again
                    if ($form->isValid()) {
                        $em->flush();

                        // update the client global balance too
                        $this->get('jcs.invoice')->updateBalance($client->getCatering());

                        $this->get('session')->getFlashBag()->add('notice', 'Befizetés elmentve');

                        return $this->render('JCSGYKAdminBundle:Catering:invoices_dialog.html.twig', [
                            'success' => true,
                        ]);
                    }
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

    public function relativeEditAction($id, $relation_id)
    {
        $request = $this->getRequest();
        $user= $this->get('security.context')->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        $client = $this->getClient($id);
        if ($relation_id != 'new') {
            $relation = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id, $relation_id);
        }

        if (empty($relation[0])) {
            $relation = new Relation();
            $relation->setChildId($id);
            $new_client = new Client($this->container->get('jcs.ds'));
            $new_client->setCompanyId($company_id);
            $new_client->setType(Client::PARENT);
            $new_client->setCreator($user);
            $new_client->setIsArchived(false);
            /*
            // set gender
            if (Relation::MOTHER == $type) {
                $new_client->setGender(2);
            } elseif (Relation::FATHER == $type) {
                $new_client->setGender(1);
            }
             */

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

        if (empty($relation)) {
            throw new BadRequestHttpException('Invalid relative id');
        }

        $form = $this->createForm(new RelativeType($this->container->get('jcs.ds'), $relation->getType(), $client->getType()), $relation->getParent());

        // save
        if ($request->isMethod('POST')) {
            $form->bind($request);

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
                // if its a mother, we must update any related client record too
                if ($relation->getId() && $relation->getType() == Relation::MOTHER) {
                    // get the related clients
                    $mother = $relation->getParent();
                    $siblings = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getChildren($mother);
                    // update the mothers name fields
                    foreach ($siblings as $sibling_rel) {
                        $sibling = $this->getClient($sibling_rel->getChildId());
                        $sibling->setMotherTitle($mother->getTitle());
                        $sibling->setMotherFirstname($mother->getFirstname());
                        $sibling->setMotherLastname($mother->getLastname());
                    }
                }

                $em->flush();

                $this->get('session')->getFlashBag()->add('notice', 'Hozzátartozó elmentve');

                return $this->render('JCSGYKAdminBundle:Dialog:relative.html.twig', [
                    'success' => true
                ]);
            }
        }
        return $this->render('JCSGYKAdminBundle:Dialog:relative.html.twig', [
            'form' => $form->createView(),
            'relative' => $relation,
        ]);
    }

    /**
     * Edits the client data
     *
     * @Secure(roles="ROLE_USER")
     */
    public function editAction($id = null, $client_type=null)
    {
        $request = $this->getRequest();

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

            // get the catering form
//            if ($client->getType() == Client::CA) {
//                $catering_form = $this->createForm(new CateringType($this->container->get('jcs.ds')), $client->getCatering());
//            }

            $form = $this->createForm(new ClientType($this->container->get('jcs.ds'), $client), $client);

            $orig_year = $client->getCaseYear();
            $orig_casenum = $client->getCaseNumber();

            // save the user
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    // set modifier user
                    $client->setModifier($user);
                    $client->setModifiedAt(new \DateTime());
                    $case_num = $client->getCaseNumber();

                    // save the new user data
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
                        }
                        else {
                            $copy_case = true;
                        }

                        // set the creator
                        $client->setCreator($user);
                        $client->setCompanyId($company_id);
                        $client->setIsArchived(false);

                        // set the client type
                        $client->setType($client_type);

                        $em->persist($client);

                        // if case number given, we must copy over a few fields from that case
                        if (!empty($copy_case)) {
                            $this->copyCaseData($client);
                        }
                    }
                    // restore the case number and year
                    elseif (empty($case_num)) {
                        $client->setCaseYear($orig_year);
                        $client->setCaseNumber($orig_casenum);
                    }

                    // create a catering record for such clients
                    if ($client->getType() == Client::CA && empty($client->getCatering())) {
                        $catering = new Catering();
                        $catering->setClient($client);
                        $em->persist($catering);
                    }
                    $em->flush();

                    // set the visible case number
                    $client->setCaseLabel($this->container->get('jcs.twig.adminextension')->formatCaseNumber($client));

                    // handle/save the utilityproviders
                    foreach ($client->getUtilityprovidernumbers() as $up) {
                        $val = $up->getValue();
                        if (empty($val)) {
                            // remove the empty providers
                            $client->removeUtilityprovidernumber($up);
                            $em->remove($up);
                        }
                        else {
                            // set the client id
                            $up->setClient($client);
                            // save the rest
                            $em->persist($up);
                        }
                    }

                    // handle/save the addresses
                    foreach ($client->getAddresses() as $adr) {
                        $val = $adr->getCity() . $adr->getStreet();
                        if (empty($val)) {
                            // remove the empty address
                            $client->removeAddress($adr);
                            $em->remove($adr);
                        }
                        else {
                            $aid = $adr->getId();
                            if (empty($aid)) {
                                // set the client id
                                $adr->setClient($client);
                                $adr->setCreator($user);
                                $em->persist($adr);
                            }
                            else {
                                $adr->setModifier($user);
                            }
                        }
                    }

                    // copy the mothers name from the relatives record
                    $mother = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($client->getId(), Relation::MOTHER);
                    if (!empty($mother[0])) {
                        $mother = $mother[0]->getParent();
                        // if the birthname is set, then we use that, otherwise the name
                        if ($mother->getBirthFirstname() || $mother->getBirthLastname()) {
                            $client->setMotherFirstname($mother->getBirthFirstname());
                            $client->setMotherLastname($mother->getBirthLastname());
                        }
                        else {
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
            }

            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);

            return $this->render('JCSGYKAdminBundle:Client:edit.html.twig', [
                'client' => $client,
                'problems' => $problems,
                'form' => $form->createView(),
                'relatives' => $relatives,
                'new_relations' => $relation_types,
                'client_type' => $client_type
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
    }

    /**
     * Copies over a few fields from the given case
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
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
                $this->copyFields($client, $sibling, ['MotherTitle', 'MotherFirstname', 'MotherLastname']);

                // save the location data if empty
                if (!$client->getCity() || !$client->getStreet()) {
                    $this->copyFields($client, $sibling, ['Country', 'ZipCode', 'City', 'Street', 'StreetType', 'StreetNumber', 'FlatNumber']);
                }
                if (!$client->getLocationCity() || !$client->getLocationStreet()) {
                    $this->copyFields($client, $sibling, ['LocationCountry', 'LocationZipCode', 'LocationCity', 'LocationStreet', 'LocationStreetType', 'LocationStreetNumber', 'LocationFlatNumber']);
                }
                // clone the last address of the case
                $addresses = $sibling->getAddresses();
                $act_addr = $addresses->last();
                if (!empty($act_addr)) {
                    $new_addr = clone $act_addr;
                    $new_addr->setClient($client);
                    $em->persist($new_addr);
                }

                // copy over the relations
                $rels = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($sibling->getId());
                foreach ($rels as $rel) {
                    $relative = new Relation();
                    $relative->setType($rel->getType());
                    $relative->setChildId($client->getId());
                    if ($rel->getType() == Relation::MOTHER) {
                        // we only link to the mother,
                        $relative->setParent($rel->getParent());
                    }
                    else {
                        // but clone the other relations
                        $old_parent = $rel->getParent();
                        $new_parent = clone $old_parent;
                        $em->persist($new_parent);
                        $relative->setParent($new_parent);
                    }

                    $em->persist($relative);
                }
                $em->flush();

                return true;
            }

            return false;
        }
    }

    private function copyFields(Client &$client, Client &$sibling, array $fields)
    {
        foreach ($fields as $field) {
            $setter = 'set' . $field;
            $getter = 'get' . $field;
            $client->$setter($sibling->$getter());
        }
    }

    /**
     * Archive clients
     *
     * @PreAuthorize("hasRole('ROLE_ADMIN') or hasRole('ROLE_ASSISTANCE')")
     */
    public function archiveAction($id)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            // get the client
            $client = $this->getClient($id);
            if (empty($client)) {
                throw new BadRequestHttpException('Invalid client id');
            }

            // check for any open problems, only arhivable if no problems are open
            $open_problems = 0;
            // get only the undeleted problems
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);

            foreach ($problems as $problem) {
                if ($problem->getIsActive()) {
                    $open_problems++;
                }
            }

            if ($open_problems > 0) {
                // can't archive, show the popup

                return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                    'client' => $client,
                    'open_problems' => $open_problems
                ]);
            }

            $archive = new Archive;
            $form = $this->createForm(new ArchiveType($this->container->get('jcs.ds'), $client->getIsArchived()), $archive);

            // save
            if ($request->isMethod('POST')) {
                $form->bind($request);

                $operation = $form->get('operation')->getData();
                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $user= $this->get('security.context')->getToken()->getUser();
                    $archive->setClient($client);
                    // set modifier user
                    $archive->setCreator($user);
                    $archive->setCreatedAt(new \DateTime());

                    $em->persist($archive);

                    // archive the client
                    $client->setIsArchived(1 - $operation);

                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Ügyfél elmentve');

                    return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                'client' => $client,
                'form' => $form->createView(),
                'open_problems' => $open_problems
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
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
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);
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
                $relatives = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id);
                $relation_types = $this->container->get('jcs.ds')->getRelationTypes();
                foreach ($relatives as $relative) {
                    if (isset($relation_types[$relative->getType()])) {
                        unset($relation_types[$relative->getType()]);
                    }
                }
            }

            return $this->render('JCSGYKAdminBundle:Client:view.html.twig', [
                'client' => $client,
                'problems' => $problems,
                'can_edit' => $client->canEdit($sec),
                'display_type' => count($client_types) > 1,  // only display the client type if there are more then one types of this company
                'relatives' => $relatives,
                'new_relations' => $relation_types,
                'client_type' => $client->getType(),
            ]);
        }
        else {
            throw new BadRequestHttpException('Invalid client id');
        }
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
     * @param string $q search string
     *
     * @Secure(roles="ROLE_USER")
     */
    public function searchAction($client_type)
    {
        if (empty($client_type)) {
            throw new BadRequestHttpException('Invalid client type');
        }
        $request = $this->getRequest();
        $q = $request->query->get('q');

        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $limit = 100;

        $re = [];
        $sql = '';

        // save the search string
        $this->get('session')->set('quicksearch.' . $client_type, $q);

        $time_start = microtime(true);
        if (!empty($q)) {
            $num_ver = Client::cleanupNum($q);
            $db = $this->get('doctrine.dbal.default_connection');
            $sql = "SELECT id, type, case_year, case_number, company_id, title, firstname, lastname, mother_firstname, mother_lastname, zip_code, city, street, street_type, street_number, flat_number FROM client WHERE";
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
}
