<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Stat;

class AssistanceController extends Controller
{
    /**
    * @Secure(roles="ROLE_ASSISTANCE")
    */
    public function indexAction()
    {
        $request = $this->getRequest();

        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', []);
    }

    /**
    * @Secure(roles="ROLE_ASSISTANCE")
    */
    public function registerInquiryAction($event)
    {
        $inquiry_events = $this->container->get('jcs.ds')->getGroup('inquiry');

        if (!isset($inquiry_events[$event])) {
            throw new HttpException(400, "Bad request");
        }

        // save the event
        $this->get('jcs.stat')->save(Stat::TYPE_INQUIRY, $event);

        $msg = $inquiry_events[$event] . ' regisztrálva';

        if ($this->getRequest()->isXmlHttpRequest()) {
            return new Response($msg);
        }
        else {
            $this->get('session')->getFlashBag()->add('notice',$msg);
        }

        return $this->redirect($this->generateUrl('home'));
    }
}