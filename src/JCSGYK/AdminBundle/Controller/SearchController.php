<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Person;

class SearchController extends Controller
{
    public function quickAction(Request $request)
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $re = [];
            
            $q = $request->get('q');
            if (!empty($q)) {
                $db = $this->get('doctrine.dbal.default_connection'); 
                $qr = $db->quote('+' . implode('* +', explode(' ', $q)) . '*');

                $sql = "SELECT title, firstname, lastname FROM person2 WHERE MATCH (title, firstname, lastname) AGAINST ({$qr} IN BOOLEAN MODE)";
                $re = $db->fetchAll($sql);

//                $q = explode(' ', trim($q));
//                $dql = 'SELECT p.firstname, p.lastname FROM JCSGYKAdminBundle:Person p WHERE %s ORDER BY p.lastname, p.firstname ASC';
//                $d2 = [];
//                for ($i = 1; $i <= count($q); $i++) {
//                    $d2[] = "CONCAT(p.firstname, p.lastname) LIKE ?{$i}";
//                }
//                $dql = sprintf($dql, implode(' AND ', $d2));
//
//                $em = $this->getDoctrine()->getManager();
//                $query = $em->createQuery($dql);
//                for ($i = 1; $i <= count($q); $i++) {
//                    $query->setParameter($i, "%{$q[$i - 1]}%");
//                }
//                $re = $query->getResult();                
            }
            
            return $this->render('JCSGYKAdminBundle:Search:quick.html.twig', ['persons' => $re]);
        }
        
    }
}