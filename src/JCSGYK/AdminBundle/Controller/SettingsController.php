<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

class SettingsController extends Controller
{
    /**
    * @Secure(roles="ROLE_USER")
    */
    public function indexAction()
    {
        $request = $this->getRequest();

        return $this->render('JCSGYKAdminBundle:Settings:index.html.twig', []);
    }
}