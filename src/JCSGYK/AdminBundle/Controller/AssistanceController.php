<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;

class AssistanceController extends Controller
{
    public function indexAction()
    {
        if ($this->get('security.context')->isGranted('ROLE_ASSISTANCE')) {
            $this->get('logger')->info('ROLE_ASSISTANCE');
        }
        $inquiry_types = $this->getInquiryTypes();

        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', array('inquiry_types' => $inquiry_types));
    }

    public function registerInquiryAction($type)
    {        
        $user = $this->get('security.context')->getToken()->getUser();
                
        if ($this->getRequest()->isXmlHttpRequest()) {
            $inquiry = new Inquiry();
            $inquiry->setInquiryTypeId($type);
            $inquiry->setUserId($user->getId());

            $em = $this->getDoctrine()->getManager();
            $em->persist($inquiry);
            $em->flush();

            //$inquiry_types = $this->getInquiryTypes();
            //$this->get('session')->getFlashBag()->set('notice', 'Érdeklődés elmentve');
        
            return new Response('Érdeklődés elmentve');
        }
        
        return $this->redirect($this->generateUrl('assistance_home'));
    }
    
    protected function getInquiryTypes()
    {
        return $this->getDoctrine()
            ->getRepository('JCSGYKAdminBundle:InquiryType')
            ->findAllOrderedByName();
    }
    
}