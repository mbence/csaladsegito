<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClientController extends Controller
{
    public function indexAction(Request $request)
    {
        return $this->render('JCSGYKAdminBundle:Search:index.html.twig', []);
    }

    public function viewAction(Request $request)
    {
        // only process ajax requests on prod env!
        if ($request->isXmlHttpRequest() || 'dev' == $this->container->getParameter('kernel.environment')) {

            $id = $request->request->get('id');
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            // get client data
            $client = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
                ->findOneBy(['id' => $id, 'companyId' => $company_id]);

            return $this->render('JCSGYKAdminBundle:Client:view.html.twig', ['client' => $client]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }
}