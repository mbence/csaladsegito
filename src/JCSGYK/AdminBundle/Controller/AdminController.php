<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JCSGYK\AdminBundle\Entity\Inquiry;

class AdminController extends Controller
{
    public function indexAction()
    {
        $ph = $this->get('jcsgyk_admin.page_header');
        $header_vars = $ph->getVars($this->getRequest());
        
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $this->get('logger')->info('ROLE_ADMIN');
        }
        
        $inquiry_types = $this->getDoctrine()
            ->getRepository('JCSGYKAdminBundle:InquiryType')
            ->findAllOrderedByName();
        
        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', array('inquiry_types' => $inquiry_types));
    }
    
    public function usersAction()
    {
        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', array('inquiry_types' => $inquiry_types));
    }    
}