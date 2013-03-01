<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

class AssistanceController extends Controller
{
    /**
    * @Secure(roles="ROLE_ASSISTANCE")
    */
    public function indexAction(Request $request)
    {
//        var_dump($this->container->get('templating.helper.assets')->getVersion());
//        var_dump($this->container->getParameter('app.version'));
//
//         $params = $this->container->get('jcs.ds');
//         var_dump($params->get(10));
//         var_dump($params->getGroup(1));


//        echo 'session: ' . ini_get('session.save_path');
//        $val = 'ffffaaa';
//        $r = preg_match('/(yyy|xxx|aaa|bbb)/', $val);
//        var_dump($r);

//        $q = "Laka Ild'sel";
//        $db = $this->get('doctrine.dbal.default_connection');
//        $qr = $db->quote('+' . implode('* +', explode(' ', $q)) . '*');
//
//        if ($this->get('security.context')->isGranted('ROLE_ASSISTANCE')) {
//            $this->get('logger')->info('ROLE_ASSISTANCE');
//        }
        //$this->get('session')->getFlashBag()->set('notice', 'Érdeklődés elmentve');

        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', []);
    }

    /**
    * @Secure(roles="ROLE_ASSISTANCE")
    */
    public function inquiryStatAction(Request $request)
    {
        $colors = ['#E07628', '#A0D8F1', '#E9AF32', '#BF381A', '#0A224E'];
        $jq_colors = [];
        foreach ($colors as $c) {
            $jq_colors[] = ['color' => $c];
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $inquiry_types = $this->container->get('jcs.ds')->getGroup(1);
        $inquiry_map = array_flip(array_reverse(array_keys($inquiry_types)));

        // get the inquiry events
        $em = $this->getDoctrine()->getManager();
        $stat = $em->createQuery("SELECT i FROM JCSGYKAdminBundle:Inquiry i WHERE i.userId=:userid AND i.createdAt>DATE_SUB(CURRENT_DATE(), 31, 'day') ORDER BY i.createdAt DESC, i.type")
            ->setParameter('userid', $user->getId())
            ->getResult();

        // create an easy to use table of days and types
        $stat_detailed = [];
        // format the monthly stat
        $stat_month = [
            'selector' => 'monthchart',
            'data' => array_pad([], count($inquiry_types), array_pad([], date('t'), 0)),
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
                        'selector' => 'daychart_' . $day_num,
                        'data' => array_pad([], count($inquiry_types), [0]),
                        // hide the ticks
                        'tick' => ['   '], //[$day->getCreatedAt()->format("m.\nd.")],
                        // show 'today', 'yesterday', or the date as tht title
                        'title' => $this->container->get('translator')->trans(!empty($daynames[$day->getCreatedAt()->format('Ymd')]) ? $daynames[$day->getCreatedAt()->format('Ymd')] : $day->getCreatedAt()->format('Y.m.d.')),
                        'max' => 0,
                        'colors' => json_encode($jq_colors)
                    ];
                }
                if (isset($inquiry_map[$day->getType()])) {
                    $stat_detailed[$day_num]['data'][$inquiry_map[$day->getType()]] = [$day->getCounter()];
                }
                if ($day->getCounter() > $day_max) {
                    $day_max = $day->getCounter();
                }
            }
            // add monthly stats, but only for this month, and only if the inquiry type is known
            if (isset($inquiry_map[$day->getType()]) && $day->getCreatedAt()->format('m') == date('m')) {
                $stat_month['data'][$inquiry_map[$day->getType()]][$day->getCreatedAt()->format('j')-1] = $day->getCounter();
            }
        }

        //var_dump($stat_month);

        // add the max numbers
        foreach ($stat_detailed as $k => $v) {
            $stat_detailed[$k]['max'] = (int) ceil($day_max * 1.15);
        }

        return $this->render('JCSGYKAdminBundle:Home:inquiryStat.html.twig', [
            'detailed_json' => json_encode($stat_detailed),
            'detailed' => $stat_detailed,
            'month_json' => json_encode($stat_month),
            //'sum' => $stat_sum,
            'types' => $inquiry_types,
            'colors' => $colors,
        ]);
    }

    /**
    * @Secure(roles="ROLE_ASSISTANCE")
    */
    public function registerInquiryAction($type)
    {

        $user = $this->get('security.context')->getToken()->getUser();

        // get inquiry types from the db param service (parameters table)
        $inquiry_types = $this->container->get('jcs.ds')->getGroup(1);
        // validate inquiry type sent
        if (!isset($inquiry_types[$type])) {
            throw new HttpException(400, "Bad request");
        }
        else {
            // look for the record for the actual type and day
            $em = $this->getDoctrine()->getManager();
            $inq = $em->createQuery('SELECT i FROM JCSGYKAdminBundle:Inquiry i WHERE i.userId=:userid AND i.createdAt=CURRENT_DATE() AND i.type=:type')
                ->setParameter('userid', $user->getId())
                ->setParameter('type', $type)
                ->getResult();

            if (empty($inq[0])) {
                // no record for today, lets create one!
                $inquiry = new Inquiry();
                $inquiry->setType($type);
                $inquiry->setUserId($user->getId());
                $inquiry->setCounter(1);
                $em->persist($inquiry);
            }
            else {
                // we already have a record, lets increase the counter
                $inq[0]->setCounter($inq[0]->getCounter() + 1);
            }
            $em->flush();

            $msg = $inquiry_types[$type] . ' regisztrálva';

            if ($this->getRequest()->isXmlHttpRequest()) {
                return new Response($msg);
            }
            else {
                $this->get('session')->setFlash('notice',$msg);
            }
        }

        return $this->redirect($this->generateUrl('home'));
    }

    /**
    * @Secure(roles="ROLE_ASSISTANCE")
    */

    public function newClientAction()
    {
        return new Response('new Client');
    }
}