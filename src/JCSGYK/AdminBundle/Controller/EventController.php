<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

class EventController extends Controller
{
    public function viewAction(Request $request)
    {
        // only process ajax requests on prod env!
        if ($this->getRequest()->isXmlHttpRequest() || 'dev' == $this->container->getParameter('kernel.environment')) {

            $id = $request->request->get('id');
            // get problem data
            $event = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Event')
                ->find($id);

            return $this->render('JCSGYKAdminBundle:Event:view.html.twig', ['event' => $event]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }
}