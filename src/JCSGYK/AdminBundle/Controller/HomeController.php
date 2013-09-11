<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

class HomeController extends Controller
{
    public function indexAction()
    {
        return $this->render('JCSGYKAdminBundle:Home:index.html.twig', []);
    }

    /**
    * @Secure(roles="ROLE_USER")
    */
    public function statAction($type)
    {
        // get the stat events
        $stat = $this->get('jcs.stat')->get($type);

        return $this->render('JCSGYKAdminBundle:Home:stat.html.twig', $stat);
    }

    public function kenysziAction()
    {
        $kenyszi = $this->get("kenyszi");
        $result = $kenyszi->run();

        return new Response($result);
    }
}
