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

        return $this->render('JCSGYKAdminBundle:Home:index.html.twig', []);
    }
}