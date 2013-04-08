<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

class HomeController extends Controller
{
    public function indexAction()
    {
        $co = $this->container->get('jcs.ds')->getCompany();

        /*
        // TODO: ASC problem order for all problems
        $problem = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->findOneBy(['id' => 19852, 'isDeleted' => 0]);
        $events = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->getEventList(19852, 'ASC');

        $content = $this->renderView(
            'JCSGYKAdminBundle:Elements:casehistory.html.twig',
            array('problem' => $problem, 'events' => $events)
        );

        $template = 'esemeny_lista.docx';

        $fields = [
            'client' => [
                'name' => 'Zuzu Petáz',
                'id' => 'Ü-1234',
                'birthdate' => '2013-10-12',
                'nonex' => 'ez nem lesz meg!'
            ],
            'doc' => [
                'date' => date('Y-m-d'),
                'body' => $content
            ]
        ];

        $this->container->get('jcs.docx')->show($template, $fields, 'hello.docx');
*/

        return $this->render('JCSGYKAdminBundle:Home:index.html.twig', []);
    }
}
