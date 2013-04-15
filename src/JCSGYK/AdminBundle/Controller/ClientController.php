<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Form\Type\ClientType;
use JCSGYK\AdminBundle\Entity\Archive;
use JCSGYK\AdminBundle\Form\Type\ArchiveType;

class ClientController extends Controller
{
    public function indexAction()
    {
        return $this->render('JCSGYKAdminBundle:Client:index.html.twig');
    }

    /**
     * Edits the client data
     */
    public function editAction($id = null)
    {
        $request = $this->getRequest();

        // TODO: utca adatbázis + ellenőrzés

        $client = null;
        $em = $this->getDoctrine()->getManager();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        if (!empty($id)) {
            // get the client data
            $client = $this->getClient($id);
        }
        else {
            // new client
            $client = new Client();
        }

        if (!empty($client)) {
            if ($client->getIsArchived()) {
                return $this->redirect($this->generateUrl('client_view', ['id' => $id]));
            }

            $form = $this->createForm(new ClientType($this->container->get('jcs.ds')), $client);

            // save the user
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {

                    $user= $this->get('security.context')->getToken()->getUser();
                    // set modifier user
                    $client->setModifier($user);

                    // save the new user data
                    if (is_null($client->getId())) {
                        // set the creator
                        $client->setCreator($user);
                        $client->setCompanyId($company_id);
                        $client->setIsArchived(false);
                        $em->persist($client);
                    }
                    // handle/save the utilityproviders

                    foreach ($client->getUtilityprovidernumbers() as $up) {
                        $val = $up->getValue();
                        if (empty($val)) {
                            // remove the empty providers
                            $client->removeUtilityprovidernumber($up);
                            $em->remove($up);
                        }
                        else {
                            // set the client id
                            $up->setClient($client);
                            // save the rest
                            $em->persist($up);
                        }
                    }

                    $em->flush();

                    $this->get('session')->setFlash('notice', 'Ügyfél elmentve');

                    //return $this->redirect($this->generateUrl('client_edit', ['id' => $client->getId()]));
                    return $this->redirect($this->generateUrl('client_view', ['id' => $client->getId()]));
                }
            }
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);

            return $this->render('JCSGYKAdminBundle:Client:edit.html.twig', ['client' => $client, 'problems' => $problems ,'form' => $form->createView()]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    public function archiveAction($id)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            // get the client
            $client = $this->getClient($id);
            if (empty($client)) {
                throw new HttpException(400, "Bad request");
            }

            // check for any open problems, only arhivable if no problems are open
            $open_problems = 0;
            // get only the undeleted problems
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);

            foreach ($problems as $problem) {
                if ($problem->getIsActive()) {
                    $open_problems++;
                }
            }

            if ($open_problems > 0) {
                // can't archive, show the popup

                return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                    'client' => $client,
                    'open_problems' => $open_problems
                ]);
            }

            $archive = new Archive;
            $form = $this->createForm(new ArchiveType($this->container->get('jcs.ds'), $client->getIsArchived()), $archive);

            // save
            if ($request->isMethod('POST')) {
                $form->bind($request);

                $operation = $form->get('operation')->getData();
                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $user= $this->get('security.context')->getToken()->getUser();
                    $archive->setClient($client);
                    // set modifier user
                    $archive->setCreator($user);
                    $archive->setCreatedAt(new \DateTime());

                    $em->persist($archive);

                    // archive the client
                    $client->setIsArchived(1 - $operation);

                    $em->flush();

                    $this->get('session')->setFlash('notice', 'Ügyfél elmentve');

                    return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Dialog:client_archive.html.twig', [
                'client' => $client,
                'form' => $form->createView(),
                'open_problems' => $open_problems
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }


    public function viewAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);
        }
        if (!empty($client)) {
            return $this->render('JCSGYKAdminBundle:Client:view.html.twig', ['client' => $client, 'problems' => $problems]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    public function problemsAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);

            return $this->render('JCSGYKAdminBundle:Client:_problems.html.twig', ['client' => $client, 'problems' => $problems]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    protected function getClient($id)
    {
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        // get client data

        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
            ->findOneBy(['id' => $id, 'companyId' => $company_id]);
    }

    public function searchAction($q)
    {
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $limit = 100;

        $re = [];
        $sql = '';

        // save the search string
        $this->get('session')->set('quicksearch', $q);

        $time_start = microtime(true);
        if (!empty($q)) {

            $db = $this->get('doctrine.dbal.default_connection');
            $sql = "SELECT id, company_id, title, firstname, lastname, mother_firstname, mother_lastname, zip_code, city, street, street_type, street_number, flat_number FROM client WHERE";
            // search for ID
            if (is_numeric($q)) {
                $sql .= " (id={$db->quote($q)} AND company_id={$db->quote($company_id)}) OR (social_security_number LIKE {$db->quote($q . '%')} AND company_id={$db->quote($company_id)})";
            }
            else {
                $search_words = explode(' ', trim($q));
                // We cant use FULLTEXT search for fields with very light weights (same values most of the times)
                // because the indexer ignores these. Street number and street types are such fields.
                // We must use HAVING after the FULLTEXT search to filter these fields.
                $last = end($search_words);
                // if the last word is a number, we use that for the street number search
                if (preg_match('/^\d+(\/|\.|-)?\w*\.?\*?$/', $last)) {
                    // remove the last element
                    array_pop($search_words);
                    // also remove any extra chars
                    $last = strtr($last, ['/' => '', '.' => '', ' ' => '', '*' => '%']);
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

                $company_id = 1;
                $xsql = ['company_id=' . $company_id];

                // if we search for street number
                if (!empty($last) || !empty($street_types)) {

                    if (!empty($last)) {
                        $xsql[] = "street_number LIKE " . $last;
                    }
                    if (!empty($street_types)) {
                        $xsql[] = "street_type IN (" . implode(',', $street_types) . ")";
                    }
                }

                $sql .= " HAVING " . implode(' AND ', $xsql);
            }
            $sql .= " ORDER BY lastname, firstname LIMIT " . $limit;
            $re = $db->fetchAll($sql);
        }
        $time_end = microtime(true);
        $time = number_format(($time_end - $time_start) * 1000, 3, ',', ' ');

        return $this->render('JCSGYKAdminBundle:Client:results.html.twig', ['clients' => $re, 'time' => $time, 'sql' => $sql, 'resnum' => count($re)]);
    }


    /**
     * Genarate a docx file with the case history of the client
     * If no problem id given, then all the problems will be listed
     * If a problem is is sopecified, then only that problem will be shown
     *
     * The generated docx file will be sent back as a downloadable file
     *
     * Uses the OpenTBS library with the OpenTBSBundle / jcs.docx service
     *
     * @param int $id Client ID
     * @param int $problem_id Problem ID (optional)
     */
    public function historyAction($id, $problem_id = null)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
        }
        if (!empty($client)) {
            // get all problems, or the specified problem if a $problem_id is provided
            if (is_null($problem_id)) {
                $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id, 'ASC');
            }
            else {
                $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->findBy(['id' => $problem_id, 'isDeleted' => 0]);
            }
            // get events
            // we cant use the Doctrine realtion to get the events, because we only need undeleted events and in ascending order
            $problem_repo = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem');
            $events = [];
            foreach ($problems as $problem) {
                $events[$problem->getId()] = $problem_repo->getEventList($problem->getId(), 'ASC');
            }

            // render the sub-template for the event list
            $content = $this->renderView(
                'JCSGYKAdminBundle:Elements:history.html.twig',
                array('problems' => $problems, 'events' => $events)
            );

            // admin twig extension for formatting
            $ae = $this->container->get('jcs.twig.adminextension');

            // field map for the template
            $data = [
                'client' => $client,
                'history' => $content,
            ];

            $send = $this->container->get('jcs.docx')->show(2, $data);

            if (!$send) {
                throw new HttpException(400, "Bad request");
            }

            exit;
        }
        else {
            // wrong client id given, no fun
            throw new HttpException(400, "Bad request");
        }
    }

    public function templatesAction($id = null, $template_id = null)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            // get the client
            $client = $this->getClient($id);
            if (empty($client)) {
                throw new HttpException(400, "Bad request");
            }

            $em = $this->getDoctrine()->getManager();
            $company_id = $this->container->get('jcs.ds')->getCompanyId();

            if (is_null($template_id)) {
                // get all active templates of this company
                $templates = $em->getRepository('JCSGYKAdminBundle:Template')->findBy(['companyId' => $company_id, 'isActive' => true], ['name' => 'ASC']);;

                return $this->render('JCSGYKAdminBundle:Dialog:client_templates.html.twig', [
                    'client' => $client,
                    'templates' => $templates
                ]);
            }
            else {
                // generate a specific document

                if (1 == $template_id) {
                    // ACST kérelem
                    $data = $this->getDebtData($client);
                }
                elseif (2 == $template_id) {
                    // Esettörténet
                    $data = $this->getHistoryData($client);
                }
                else {
                    $data = ['client' => $client];
                }

                $send = $this->container->get('jcs.docx')->show($template_id, $data);

                if (!$send) {
                    throw new HttpException(400, "Bad request");
                }

                exit;
            }
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    private function getHistoryData(Client $client)
    {
        // get all problems
        $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($client->getId(), 'ASC');

        // get events
        // we cant use the Doctrine realtion to get the events, because we only need undeleted events and in ascending order
        $problem_repo = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem');
        $events = [];
        foreach ($problems as $problem) {
            $events[$problem->getId()] = $problem_repo->getEventList($problem->getId(), 'ASC');
        }

        // render the sub-template for the event list
        $content = $this->renderView(
            'JCSGYKAdminBundle:Elements:history.html.twig',
            array('problems' => $problems, 'events' => $events)
        );

        // return the field map for the template
        return ['client' => $client, 'history' => $content];
    }


    /**
     * finds all the active problems, and get the debts
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @return array
     */
    private function getDebtData(Client $client)
    {
        $em = $this->getDoctrine()->getManager();
        // find all active problems
        $problems = $em->getRepository('JCSGYKAdminBundle:Problem')->findBy(['client' => $client, 'isActive' => true, 'isDeleted' => false]);
        // get the problem ids
        $problem_ids = [];
        foreach ($problems as $problem) {
            $problem_ids[] = $problem->getId();
        }

        // get the debt records
        $debts = $em
            ->createQuery("SELECT d FROM JCSGYKAdminBundle:Debt d WHERE d.problem IN (:problems)")
            ->setParameter('problems', $problem_ids)
            ->getResult();

        // get all provider
        $debt_list = $this->getDebtMap();

        // arrange the debts by utility provider
        foreach ($debts as $debt) {
            $up_id = $debt->getUtilityprovider()->getId();
            // check to see if the provider already exists
            if (!isset($debt_list[$up_id])) {
                // theoretically we should never go in to this branch, beacuse the getDebtMap()
                $debt_list[$up_id] = [
                    'key' => $debt->getUtilityprovider()->getTemplateKey(),
                    'managed' => 0,
                    'registered' => 0
                ];
            }

            $debt_list[$up_id]['managed'] += $debt->getManagedDebt();
            $debt_list[$up_id]['registered'] += $debt->getRegisteredDebt();
        }

        return [
            'client' => $client,
            'debts' => $debt_list
        ];
    }

    private function getDebtMap()
    {
        $em = $this->getDoctrine()->getManager();
        // get all utility provider keys
        $ups = $em->getRepository("JCSGYKAdminBundle:Utilityprovider")->findAll();
        $debt_list = [];

        foreach ($ups as $up) {
            $debt_list[$up->getId()] = [
                'key' => $up->getTemplateKey(),
                'managed' => 0,
                'registered' => 0
            ];
        }

        return $debt_list;
    }
}