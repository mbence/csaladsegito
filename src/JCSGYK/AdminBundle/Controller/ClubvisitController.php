<?php

namespace JCSGYK\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use JCSGYK\AdminBundle\Entity\HomeHelp;
use JCSGYK\AdminBundle\Services\DataStore;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\ClubVisit;
use JCSGYK\AdminBundle\Entity\Club;

class ClubvisitController extends Controller
{
    /**
     * Admin Homehelp table editor
     *
     * @param Request $request
     * @param null $club_id
     * @param string $date
     * @return array|Response
     *
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_CATERING')")
     * @Route("/admin/visits/{club_id}/{date}", name="admin_visits")
     * @Template("JCSGYKAdminBundle:Admin:clubvisit.html.twig")
     */
    public function clubVisitAction(Request $request, $club_id = null, $date = null)
    {
        $ds = $this->container->get('jcs.ds');
        $user = $ds->getUser();
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        // clients
        $clients = $this->getClubClients($club_id);
        // get the club entity
        /** @var Club $club */
        $club = $em->getRepository('JCSGYKAdminBundle:Club')->find($club_id);
        // check club id
        if (empty($club)) {
            throw new BadRequestHttpException('Invalid club id');
        }
        // events
        $events = $ds->getGroup('club_events');

        if (empty($date)) {
            $date = 'today';
        }
        $date = (new \DateTime($date))->setTime(0, 0, 0);

        // get the record from db
        $visits = $this->getVisits($club, $date);

        // create new if no record found
        if (empty($visits)) {
            // create new records
            $visits = [];
            foreach ($clients as $client) {
                $visit = (new ClubVisit())
                    ->setCompanyId($ds->getCompanyId())
                    ->setClient($client)
                    ->setDate($date)
                    ->setEvents([])
                    ->setVisit(true)
                ;
                $visits[] = $visit;
            }
        }

        // index the visits
        $indexed_visits = [];
        foreach ($visits as $visit) {
            $indexed_visits[$visit->getClient()->getId()] = $visit;
        }
        $visits = $indexed_visits;
        unset($indexed_visits);

        // what if there are new clients, that are not present in the saved $visits list
        foreach ($clients as $client) {
            if (!isset($visits[$client->getId()])) {
                $visit = (new ClubVisit())
                    ->setCompanyId($ds->getCompanyId())
                    ->setClient($client)
                    ->setDate($date)
                    ->setEvents([])
                    ->setVisit(true)
                ;
                $visits[$client->getid()] = $visit;
            }
            // TODO: we should reorder the visits by the client name
        }

        // we need the row headers later
        $row_headers = $this->getClientNames($visits);

        $form = $this->clubVisitForm($visits, $club, $date, $events);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // table data from the form field
            $visits_data = json_decode($form->get('value')->getData(), true);
            if (empty($visits_data)) {
                $visits_data = [];
            }

            foreach ($visits_data as $vdata) {
                $client_id = $vdata[0];
                if (!empty($visits[$client_id])) {
                    $visits[$client_id]->setVisit($vdata[1]);
                    $event_data = array_slice($vdata, 2);
                    // check events
                    foreach ($event_data as &$event) {
                        $event = empty($event) ? false : true;
                    }
                    $visits[$client_id]->setEvents($event_data);
                }
            }

            foreach ($visits as $visit) {
                if (empty($visit->getId())) {
                    $visit->setCreatedBy($user->getId());
                    $visit->setCreatedAt(new \DateTime());
                    $em->persist($visit);
                }
                else {
                    $visit->setModifiedBy($user->getId());
                    $visit->setModifiedAt(new \DateTime());
                }
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Látogatás elmentve');

            return $this->redirect(
                $this->generateUrl('admin_visits', [
                    'club_id' => $club->getId(),
                    'date'    => $date->format('Y-m-d')
                ])
            );
        }

        return [
            'form'           => $form->createview(),
            'filter_form'    => $this->clubVisitFilter($club, $date)->createView(),
            'table_defaults' => $this->getClubVisitDefaults($date, $row_headers, $events),
            'date'           => $date->format('Y-m-d'),
            'club'           => $club,
        ];
    }

    /**
     * Return a list of formatted client names
     *
     * @param ClubVisit[] $visits
     * @return array
     */
    private function getClientNames($visits)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        $names = [];
        foreach ($visits as $visit) {
            $names[] = $ae->formatClientName($visit->getClient());
        }

        return $names;
    }

    /**
     * Return the ClubVisit records for this club
     * @param $club_id
     * @param \DateTime $date
     * @return ClubVisit[]
     */
    private function getVisits(Club $club, \DateTime $date)
    {
        $ds = $this->container->get('jcs.ds');
        $em = $this->container->get('doctrine')->getManager();

        return $em->createQuery("SELECT v FROM JCSGYKAdminBundle:ClubVisit v JOIN v.client c JOIN c.homehelp h WHERE c.companyId = :company AND h.club = :club AND v.date = :date ORDER BY c.lastname, c.firstname")
            ->SetParameter('company', $ds->getCompanyId())
            ->SetParameter('club', $club)
            ->SetParameter('date', $date)
            ->getResult();
    }

    /**
     * Filter form for the home help editor
     *
     * @param Club $club
     * @param \DateTime $date
     * @return \Symfony\Component\Form\Form
     */
    private function clubVisitFilter(Club $club, \DateTime $date)
    {
        $ds = $this->container->get('jcs.ds');
        $ae = $this->container->get('jcs.twig.adminextension');

        $defaults = [
            'date' => $date,
            'club' => $club,
        ];
        $prev_day = clone $date;
        $prev_day->modify('-1 day');
        $next_day = clone $date;
        $next_day->modify('+1 day');

        // build the filter form
        $form_builder = $this->createFormBuilder($defaults)
            // final url will be set by the js function "setupHomehelp" in jcssettings.coffee
            ->setAction($this->generateUrl('admin_visits'))
            ->setMethod('GET')
            ->add('date', 'date', [
                'label'    => 'Nap',
                'widget'   => 'single_text',
                'attr'     => ['class' => 'datepicker', 'type' => 'text'],
                'required' => false,
            ])
            ->add('club', 'entity', [
                'label'    => 'Klub',
                'class'    => 'JCSGYKAdminBundle:Club',
                'choices'  => $ds->getClubs(HomeHelp::VISIT),
                'required' => true,
            ])
            ->add('back', 'button', [
                'label' => '<',
                'attr'  => [
                    'class' => 'greybutton smallbutton',
                    'style' => 'margin: 0 1px 2px 0;',
                    'value'  => $prev_day->format('Y-m-d'),
                ],
            ])
            ->add('forward', 'button', [
                'label' => '>',
                'attr'  => [
                    'class' => 'greybutton smallbutton',
                    'style' => 'margin: 0 0 2px 1px;',
                    'value'  => $next_day->format('Y-m-d'),
                ],
            ])
        ;

        return $form_builder->getForm();
    }

    /**
     * Get the main form for the ClubVisit editor
     *
     * @param Client[] $visits
     * @return \Symfony\Component\Form\Form
     */
    private function clubVisitForm(array $visits, Club $club, \DateTime $date, $events = [])
    {
        $form_action = $this->generateUrl('admin_visits', ['club_id' => $club->getId(), 'date' => $date->format('Y-m-d')]);
        $visit_data = [];
        foreach ($visits as $visit) {
            $client_id = $visit->getClient()->getId();
            $visited = $visit->getVisit() ? true : false;
            $event_data = $visit->getEvents();
            $vdata = [
                $client_id,
                $visited
            ];
            if (empty($event_data)) {
                $event_data = array_fill(2, count($events), false);
            }
            $vdata = array_merge($vdata, $event_data);
            $visit_data[] = $vdata;
        }

        // build the form
        $form_builder = $this->createFormBuilder(['value' => json_encode($visit_data)])
            ->setAction($form_action)
            ->setMethod('POST')
            ->add('value', 'hidden')
        ;

        return $form_builder->getForm();
    }

    /**
     * Get the default settings for the home help handsontable
     *
     * @param \DateTime $month
     * @param array $row_headers
     * @return string  json encoded array
     */
    private function getClubVisitDefaults(\DateTime $date, array $row_headers, array $events = [])
    {
        $event_count = count($events);

        $re = [
            'minSpareRows'          => 0,
            'cells'                 => false,
            'colWidths'             => [120],
            'colHeaders'            => ['Látogatás'],
            'rowHeaders'            => $row_headers,
            'columns'               => [
                [
                    'data' => 1,
                    'type' => 'checkbox',
                ]
            ],
        ];
        $n = 2;
        foreach ($events as $event) {
            $re['columns'][]    = [
                'data'     => $n,
                'type'     => 'checkbox',
            ];
            $re['colHeaders'][] = $event;
            $re['colWidths'][]  = 80;
            $n++;
        }

        return json_encode($re);
    }

    /**
     * Find the clients of the selected club
     * Also update the $club_id to the first of the list if none selected
     *
     * @param $club_id
     * @return Client[]
     */
    private function getClubClients(&$club_id)
    {
        /** @var DataStore $ds */
        $ds = $this->container->get('jcs.ds');
        $em = $this->container->get('doctrine')->getManager();

        // if no club provided, select the first from the list
        if (empty($club_id)) {
            $clubs = $ds->getClubs();
            $first_club = reset($clubs);
            $club_id = $first_club ? $first_club->getId() : false;
        }
        // find the clients of this club
        $clients = [];
        if (!empty($club_id)) {
            $clients = $em->getRepository('JCSGYKAdminBundle:HomeHelp')->getClientsByClub($club_id, $ds->getCompanyId());
        }

        return $clients;
    }
}
