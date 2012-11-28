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
        $limit = 100;
        
        if ($this->getRequest()->isXmlHttpRequest()) {
            $re = [];
            
            $q = $request->get('q');
            
            // save the search string
            $this->get('session')->set('quicksearch', $q);

            $time_start = microtime(true);
            if (!empty($q)) {
                
                $db = $this->get('doctrine.dbal.default_connection'); 
                $sql = "SELECT id, title, firstname, lastname, mother_firstname, mother_lastname, zip_code, city, street, street_type, street_number, flat_number FROM person WHERE";
                // search for ID
                if (is_numeric($q)) {
                    $sql .= " id=" . $db->quote($q);
                }
                else {
                    $search_words = explode(' ', trim($q));
                    $last = end($search_words);
                    // if the last word is a number, we use that for the street number search
                    if (is_numeric($last)) {
                        array_pop($search_words);
                        $last .= '%';
                    }
                    else {
                        $last = false;
                    }
                    $qr = $db->quote('+' . implode('* +', $search_words) . '*');

                    $sql .= " MATCH (firstname, lastname, street, street_type) AGAINST ({$qr} IN BOOLEAN MODE)";
                    
                    // if we search for street number
                    if ($last) {
                        $sql .= " HAVING street_number LIKE " . $db->quote($last);
                    }
                }
                $sql .= " ORDER BY lastname, firstname LIMIT " . $limit;
                $re = $db->fetchAll($sql);
            }
            $time_end = microtime(true);
            $time = number_format(($time_end - $time_start) * 1000, 3, ',', ' ');
            
            return $this->render('JCSGYKAdminBundle:Search:quick.html.twig', ['persons' => $re, 'time' => $time, 'sql' => $sql]);
        }
        
    }
}