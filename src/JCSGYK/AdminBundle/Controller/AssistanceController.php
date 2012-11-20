<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JCSGYK\AdminBundle\Entity\Inquiry;

class AssistanceController extends Controller
{
    public function indexAction()
    {
        $inquiry_types = $this->getDoctrine()
            ->getRepository('JCSGYKAdminBundle:InquiryType')
            ->findAllOrderedByName();
        
        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', array('inquiry_types' => $inquiry_types));
    }

    public function registerInquiryAction($type)
    {        
        $inquiry = new Inquiry();
        $inquiry->setInquiryTypeId($type);
        $inquiry->setUserId(1);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($inquiry);
        $em->flush();
        
        return $this->redirect($this->generateUrl('home'));
    }
    
}