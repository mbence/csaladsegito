<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Form\Type\UserType;

class SettingsController extends Controller
{
    /**
    * @Secure(roles="ROLE_USER")
    */
    public function indexAction()
    {
        $request = $this->getRequest();

        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();
        $form = $this->createForm(new UserType($this->container->get('jcs.ds'), $sec), $user);

        return $this->render('JCSGYKAdminBundle:Settings:password.html.twig', ['form' => $form->createView()]);
    }
}