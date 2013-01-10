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
            $sql = '';

            $q = $request->get('q');

            // save the search string
            $this->get('session')->set('quicksearch', $q);

            $time_start = microtime(true);
            if (!empty($q)) {

                $db = $this->get('doctrine.dbal.default_connection');
                $sql = "SELECT id, title, firstname, lastname, mother_firstname, mother_lastname, zip_code, city, street, street_type, street_number, flat_number FROM person WHERE";
                // search for ID
                if (is_numeric($q)) {
                    $sql .= " id={$db->quote($q)} OR social_security_number LIKE {$db->quote($q . '%')}";
                }
                else {
                    $search_words = explode(' ', trim($q));
                    // We cant use FULLTEXT search for fields with very light weights (same values most of the times)
                    // because the indexer ignores these. Street number and street types are such fields.
                    // We must use HAVING after the FULLTEXT search to filter these fields.
                    $last = end($search_words);
                    // if the last word is a number, we use that for the street number search
                    if (preg_match('/^\d+(\/|\.|-)?\w*\.?$/', $last)) {
                        // remove the last element
                        array_pop($search_words);
                        // also remove any extra chars
                        $last = strtr($last, ['/' => '', '.' => '', ' ' => '']);
                        //$last .= '%';
                        $last = $db->quote($last);
                    }
                    else {
                        $last = false;
                    }
                    // check for street types
                    $street_types = [];
                    // TODO: we need to find a good location for this stree type list:
                    $stype_list = [ 'akna', 'alsó', 'alsósor', 'állomás', 'árok', 'átjáró', 'bányatelep', 'bástya', 'bástyája', 'csónakházak', 'domb', 'dűlő', 'dűlőút', 'emlékpark', 'erdészház', 'erdő', 'erdősor', 'fasor', 'fasora', 'felső', 'felsősor', 'forduló', 'főtér', 'gát', 'gyümölcsös', 'határsor', 'határút', 'hegy', 'iskola', 'kapu', 'kert', 'kertek', 'kolónia', 'körönd', 'körtér', 'körút', 'körútja', 'köz', 'középsor', 'kültelek', 'lakópark', 'lejáró', 'lejtő', 'lépcső', 'lépcsősor', 'liget', 'major', 'MÁV pályaudvar', 'menedékház', 'mélyút', 'oldal', 'őrház', 'őrházak', 'park', 'parkja', 'part', 'pályaudvar', 'puszta', 'rakpart', 'rét', 'sétaút', 'sétány', 'sor', 'sportpálya', 'sugárút', 'szőlőhegy', 'tag', 'tanya', 'tanyák', 'telep', 'tere', 'tető', 'tér', 'turistaház', 'udvar', 'utca', 'utcája', 'út', 'útja', 'üdülőpart', 'vadászház', 'vasútállomás', 'vár', 'vízmű', 'víztároló', 'völgy', 'zártkert', 'zug'];
                    $stype_shorts = ['u' => 'utca', 'u.' => 'utca', 'krt' => 'körút', 'krt.' => 'körút'];
                    foreach ($search_words as $sk => $sw) {
                        if (in_array($sw, $stype_list)) {
                            $street_types[] = $db->quote($sw);
                            unset($search_words[$sk]);
                            continue;
                        }
                        // check for street type short versions
                        if (isset($stype_shorts[$sw])) {
                            $street_types[] = $db->quote($stype_shorts[$sw]);
                            unset($search_words[$sk]);
                        }
                    }

                    $qr = $db->quote('+' . implode('* +', $search_words) . '*');

                    $sql .= " MATCH (firstname, lastname, street) AGAINST ({$qr} IN BOOLEAN MODE)";

                    // if we search for street number
                    if (!empty($last) || !empty($street_types)) {
                        $xsql = [];
                        if (!empty($last)) {
                            $xsql[] = "street_number = " . $last;
                        }
                        if (!empty($street_types)) {
                            $xsql[] = "street_type IN (" . implode(',', $street_types) . ")";
                        }

                        $sql .= " HAVING " . implode(' AND ', $xsql);
                    }
                }
                $sql .= " ORDER BY lastname, firstname LIMIT " . $limit;
                $re = $db->fetchAll($sql);
            }
            $time_end = microtime(true);
            $time = number_format(($time_end - $time_start) * 1000, 3, ',', ' ');

            return $this->render('JCSGYKAdminBundle:Search:quick.html.twig', ['persons' => $re, 'time' => $time, 'sql' => $sql, 'resnum' => count($re)]);
        }

    }
}