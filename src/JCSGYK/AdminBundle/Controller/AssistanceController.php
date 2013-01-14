<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssistanceController extends Controller
{
    public function indexAction()
    {
//        var_dump($this->container->get('templating.helper.assets')->getVersion());
//        var_dump($this->container->getParameter('app.version'));
        $debug = '';

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

        if ($this->getRequest()->isXmlHttpRequest()) {
            $inquiry = new Inquiry();
            $inquiry->setInquiryTypeId($type);
            $inquiry->setUserId($user->getId());

            $em = $this->getDoctrine()->getManager();
            $em->persist($inquiry);
            $em->flush();

            $re = $this->getDoctrine()->getEntityManager()
                ->createQuery("SELECT p FROM JCSGYKAdminBundle:InquiryType p WHERE p.id={$type} ORDER BY p.name ASC")
                ->getResult();

            return new Response($re[0]->getName() . ' regisztrálva');
        }

        return $this->redirect($this->generateUrl('assistance_home'));
    }



    public function newPersonAction()
    {
        return new Response('new person');
    }


    public function getPersonAction(Request $request)
    {
        // only process ajax requests!
//        if ($this->getRequest()->isXmlHttpRequest()) {
            $id = $request->get('id');
            // get person data
            $person = $this->getDoctrine()
            ->getRepository('JCSGYKAdminBundle:Person')
            ->find($id);

            $ups = $this->getDoctrine()
            ->getRepository('JCSGYKAdminBundle:UtilityproviderId')
            ->findProviders($id);

            return $this->render('JCSGYKAdminBundle:Assistance:getperson.html.twig', ['person' => $person, 'providers' => $ups]);
//        }
//        else {
//            throw new HttpException(400, "Bad request");
//        }
    }

}