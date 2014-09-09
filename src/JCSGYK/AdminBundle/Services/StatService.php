<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Stat;

/**
 * Statistics Service
 */
class StatService
{
    /** Service container */
    private $sec;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Save a statistics event
     *
     * @param int $type Stat type
     * @param int $event Event
     * @param int $user_id User id, optional
     *
     * @Secure(roles="ROLE_USER")
     */
    public function save($type, $event, $user_id = null, $do_flush = true)
    {
        $em = $this->container->get('doctrine')->getManager();
        if (is_null($user_id)) {
            $user_id = $this->container->get('security.context')->getToken()->getUser()->getId();
        }

        // look for the record for the actual type, event and day
        $stats = $em->createQuery('SELECT s FROM JCSGYKAdminBundle:Stat s WHERE s.userId=:userid AND s.createdAt=CURRENT_DATE() AND s.type=:type AND s.event=:event')
            ->setParameter('userid', $user_id)
            ->setParameter('type', $type)
            ->setParameter('event', $event)
            ->getResult();

        if (empty($stats[0])) {
            // no record for today, lets create one!
            $stat = new Stat();
            $stat->setType($type);
            $stat->setEvent($event);
            $stat->setUserId($user_id);
            // save
            $em->persist($stat);
        }
        else {
            // we already have a record, lets increase the counter
            $stats[0]->setCounter($stats[0]->getCounter() + 1);
        }

        if ($do_flush) {
            $em->flush();
        }

        return true;
    }

    /**
     * Returns the event list of the give stat type
     *
     * @param int $type
     */
    private function getEvents($type)
    {
       if ($type == Stat::TYPE_INQUIRY) {
           $inqs = $this->container->get('jcs.ds')->getGroup('inquiry');
           return !empty($inqs) ? $inqs : [];
       }
       elseif ($type == Stat::TYPE_FAMILY_HELP) {
           $events = $this->container->getParameter('stat_events');

           return isset($events[$type]) ? $events[$type] : [];
       }
    }

    /**
     * Return the statistical data of the last 30 days of the given type (and user)
     *
     * @param int $type
     * @param int $user_id User id (optional, defaults to current user)
     */
    public function get($type, $user_id = null)
    {
        if (is_null($user_id)) {
            $user_id = $this->container->get('security.context')->getToken()->getUser()->getId();
        }
        $em = $this->container->get('doctrine')->getManager();

        $colors = ['#E07628', '#A0D8F1', '#E9AF32', '#BF381A', '#0A224E'];
        $jq_colors = [];
        foreach ($colors as $c) {
            $jq_colors[] = ['color' => $c];
        }

        // store the inquiry types in an easy to use array map
        $events = $this->getEvents($type);

        $inquiry_map = array_flip(array_reverse(array_keys($events)));

        // get the stat events
        $stat = $em->createQuery("SELECT s FROM JCSGYKAdminBundle:Stat s WHERE s.userId=:userid AND s.type=:type AND s.createdAt>DATE_SUB(CURRENT_DATE(), 31, 'day') ORDER BY s.createdAt DESC, s.event")
            ->setParameter('type', $type)
            ->setParameter('userid', $user_id)
            ->getResult();

        // create an easy to use table of days and events
        $stat_detailed = [];
        // format the monthly stat
        $stat_month = [
            'selector' => 'monthchart_' . $type,
            'data' => array_pad([], count($events), array_pad([], date('t'), 0)),
            'title' => $this->container->get('jcs.ds')->getMonth(date('n')),
            'colors' => json_encode($jq_colors)
        ];

        // number of days to show in detailed view
        $detailed_days = $day_count = 2;
        $act_day = 0;
        $day_max = 0;

        // helper array for the title
        $daynames = [
            date('Ymd') => 'ma',
            date('Ymd', strtotime("yesterday")) => 'tegnap'
        ];

        foreach ($stat as $day) {
            $idate = $day->getCreatedAt()->format('Ymd');
            if ($act_day != $idate) {
                // day change
                $act_day = $idate;
                $day_count--;
            }
            $day_num = $detailed_days - $day_count - 1;
            if ($day_count >= 0) {
                if (empty($stat_detailed[$day_num])) {
                    $stat_detailed[$day_num] = [
                        'selector' => 'daychart_' . $type . '_' . $day_num,
                        'data' => array_pad([], count($events), [0]),
                        // hide the ticks
                        'tick' => ['   '],
                        // show 'today', 'yesterday', or the date as tht title
                        'title' => $this->container->get('translator')->trans(!empty($daynames[$day->getCreatedAt()->format('Ymd')]) ? $daynames[$day->getCreatedAt()->format('Ymd')] : $day->getCreatedAt()->format('Y.m.d.')),
                        'max' => 0,
                        'colors' => json_encode($jq_colors)
                    ];
                }
                if (isset($inquiry_map[$day->getEvent()])) {
                    $stat_detailed[$day_num]['data'][$inquiry_map[$day->getEvent()]] = [$day->getCounter()];
                }
                if ($day->getCounter() > $day_max) {
                    $day_max = $day->getCounter();
                }
                // make sure that there is an entry for the actual day
                if (count($stat_detailed) == 1 && $idate != date("Ymd")) {
                    // add the actual day if there is no data
                    // duplicate the entry
                    array_unshift($stat_detailed, $stat_detailed[0]);
                    $day_count--;
                    $stat_detailed[0]['title'] = $this->container->get('translator')->trans($daynames[date('Ymd')]);
                    $stat_detailed[1]['selector'] = 'daychart_' . $type . '_1';
                    foreach ($stat_detailed[0]['data'] as $k => $v) {
                        $stat_detailed[0]['data'][$k] = [0];
                    }
                }
            }
            // add monthly stats, but only for this month, and only if the inquiry type is known
            if (isset($inquiry_map[$day->getEvent()]) && $day->getCreatedAt()->format('m') == date('m')) {
                $stat_month['data'][$inquiry_map[$day->getEvent()]][$day->getCreatedAt()->format('j')-1] = $day->getCounter();
            }
        }

        // add the max numbers
        foreach ($stat_detailed as $k => $v) {
            $stat_detailed[$k]['max'] = (int) ceil($day_max * 1.15);
        }

        // return the array needed by the view
        return [
            'detailed' => $stat_detailed,
            'month' => $stat_month,
            'events' => $events,
            'colors' => $colors,
            'type' => $type,
        ];
    }
}