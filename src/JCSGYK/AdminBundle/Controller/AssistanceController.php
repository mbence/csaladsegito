<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;

class AssistanceController extends Controller
{
    public function indexAction()
    {
//        $q = "Laka Ild'sel";
//        $db = $this->get('doctrine.dbal.default_connection'); 
//        $qr = $db->quote('+' . implode('* +', explode(' ', $q)) . '*');
//        
//        $sql = "SELECT title, firstname, lastname FROM person2 WHERE MATCH (title, firstname, lastname) AGAINST ({$qr} IN BOOLEAN MODE)";
//        $res = $db->fetchAll($sql);
//        var_dump($res);
        
//        if ($this->get('security.context')->isGranted('ROLE_ASSISTANCE')) {
//            $this->get('logger')->info('ROLE_ASSISTANCE');
//        }
        //$this->get('session')->getFlashBag()->set('notice', 'Érdeklődés elmentve');
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

            $re = $this->getDoctrine()->getEntityManager()
                ->createQuery("SELECT p FROM JCSGYKAdminBundle:InquiryType p WHERE p.id={$type} ORDER BY p.name ASC")
                ->getResult();
            
            return new Response($re[0]->getName() . ' regisztrálva');
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