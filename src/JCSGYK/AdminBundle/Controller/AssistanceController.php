<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssistanceController extends Controller
{
    public function indexAction(Request $request)
    {
//        var_dump($this->container->get('templating.helper.assets')->getVersion());
//        var_dump($this->container->getParameter('app.version'));
//
//         $params = $this->container->get('jcsgyk_admin.db_params');
//         var_dump($params->get(10));
//         var_dump($params->getGroup(1));


//        echo 'session: ' . ini_get('session.save_path');
//        $val = 'ffffaaa';
//        $r = preg_match('/(yyy|xxx|aaa|bbb)/', $val);
//        var_dump($r);

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

        return $this->render('JCSGYKAdminBundle:Assistance:index.html.twig', []);
    }

    public function registerInquiryAction($type)
    {

        $user = $this->get('security.context')->getToken()->getUser();
        $company_id = $this->container->get('jcsgyk_admin.db_params')->getCompanyId();

        // get inquiry types from the db param service (parameters table)
        $inquiry_types = $this->container->get('jcsgyk_admin.db_params')->getGroup(1);
        // validate inquiry type sent
        if (!isset($inquiry_types[$type])) {
            throw new HttpException(400, "Bad request");
        }
        else {
            $inquiry = new Inquiry();
            $inquiry->setCompanyId($company_id);
            $inquiry->setType($type);
            $inquiry->setUserId($user->getId());

            $em = $this->getDoctrine()->getManager();
            $em->persist($inquiry);
            $em->flush();

            $msg = $inquiry_types[$type] . ' regisztrálva';

            if ($this->getRequest()->isXmlHttpRequest()) {
                return new Response($msg);
            }
            else {
                $this->get('session')->setFlash('notice',$msg);
            }
        }

        return $this->redirect($this->generateUrl('assistance_home'));
    }

    public function newPersonAction()
    {
        return new Response('new person');
    }

    public function getPersonAction(Request $request)
    {
        // only process ajax requests on prod env!
        if ($this->getRequest()->isXmlHttpRequest() || 'dev' == $this->container->getParameter('kernel.environment')) {
            $id = $request->get('id');
            $company_id = $this->container->get('jcsgyk_admin.db_params')->getCompanyId();
            // get person data
            $person = $this->getDoctrine()
            ->getRepository('JCSGYKAdminBundle:Person')
            ->findBy(['id' => $id, 'companyId' => $company_id]);

            $ups = $this->getDoctrine()
            ->getRepository('JCSGYKAdminBundle:Utilityprovider')
            ->findProviders($id);

            if (empty($person[0])) {
                throw new HttpException(400, "Bad request");
            }

            return $this->render('JCSGYKAdminBundle:Assistance:getperson.html.twig', ['person' => $person[0], 'providers' => $ups]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }
}