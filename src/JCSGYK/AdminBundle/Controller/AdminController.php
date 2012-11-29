<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
    
    public function updateAction(Request $request)
    {
        $ex = [];
        $re = [];
        $session = $this->get('session');
        
        if ($request->isMethod('POST') && $request->get('update')) {
            // git pull
            $ex[] = "git pull https://github.com/mbence/csaladsegito.git master 2>&1";
            // clear cache
            $ex[] = "php ../app/console cache:clear --env=prod --no-debug 2>&1";
            // assetic dump
            $ex[] = "php ../app/console assetic:dump --env=prod --no-debug 2>&1";
            
            foreach ($ex as $com) {
                $output = '';
                $return_val = '';
                exec($com, $output, $return_val);
                $re [] = ['ex' => $com, 'return_val' => $return_val, 'output' => implode($output, '<br>')];
            }
            
            $session->set('update', $re);
            
            return $this->redirect($this->generateUrl('admin_update'));
        }        
        
        if ($session->has('update')) {
            $re = $session->get('update');
            //$session->remove('update');
        }       
        return $this->render('JCSGYKAdminBundle:Admin:update.html.twig', ['result' => $re]);
    }
}