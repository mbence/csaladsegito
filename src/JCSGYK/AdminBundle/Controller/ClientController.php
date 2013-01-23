<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

class ClientController extends Controller
{
    public function indexAction(Request $request)
    {
        return $this->render('JCSGYKAdminBundle:Search:index.html.twig', []);
    }

    public function viewAction(Request $request)
    {
        // only process ajax requests on prod env!
        if ($this->getRequest()->isXmlHttpRequest() || 'dev' == $this->container->getParameter('kernel.environment')) {

            $route = $request->attributes->get('_route');
            $routemap = [
                'assistance_view' => 'assistance',
                'familyhelp_view' => 'familyhelp',
                'childwelfare_view' => 'childwelfare',
                'admin_view' => 'admin'
            ];
            $search_type = isset($routemap[$route]) ? $routemap[$route] : reset($routemap);

            $id = $request->request->get('id');
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            // get client data
            try {
                $client = $this->getDoctrine()->getEntityManager()
                    ->createQuery('SELECT p, c, m FROM JCSGYKAdminBundle:Client p JOIN p.creator c JOIN p.modifier m WHERE p.id=:id AND p.companyId=:company')
                    ->setParameter('id', $id)
                    ->setParameter('company', $company_id)
                    ->getSingleResult();
            } catch (\Doctrine\ORM\NoResultException $e) {
                throw new HttpException(400, "Bad request");
            }

            $problems = $client->getProblems();
            if (!empty($problems)) {
                var_dump($problems[0]->getDebts()->toArray());
            }


            return $this->render('JCSGYKAdminBundle:Client:view.html.twig', ['client' => $client, 'type' => $search_type]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }
}