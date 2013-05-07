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
use JCSGYK\AdminBundle\Entity\Task;
use JCSGYK\AdminBundle\Entity\Stat;

class ClientController extends Controller
{
    /**
     * Starting point for the client menu
     *
     * @Secure(roles="ROLE_USER")
     */
    public function indexAction($client_id = null, $problem_id = null)
    {
        return $this->render('JCSGYKAdminBundle:Client:index.html.twig', ['client_id' => $client_id, 'problem_id' => $problem_id]);
    }

    /**
     * Register a client visit
     *
     * @Secure(roles="ROLE_ASSISTANCE")
     */
    public function visitAction($id = null)
    {
        // find the users. We need the case admin first, then the assignees of the problems, and then everyone else
        // only active users will be displayed
        $request = $this->getRequest();

        if (!empty($id)) {

            $em = $this->getDoctrine()->getManager();
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            $ae = $this->container->get('jcs.twig.adminextension');
            $sec = $this->get('security.context');
            $user= $sec->getToken()->getUser();

            $userlist = [];

            $user_counts = [
                'case_admin' => 0,
                'assignees' => 0,
                'all' => 0
            ];
            $listed_user_ids = [];

            // get the client
            $client = $this->getClient($id);
            // case admin
            $ca = $client->getCaseAdmin();
            if (!empty($ca) && $ca->isEnabled()) {
                $userlist[$ca->getId()] = $ae->formatName($ca->getFirstname(), $ca->getLastname());
                $user_counts['case_admin']++;
                $listed_user_ids[] = $ca->getId();
            }
            // problem assignees
            $problems = $client->getProblems();
            foreach ($problems as $problem) {
                $assignee = $problem->getAssignee();
                // we only need a user if not already on the list, and are active
                if (!empty($assignee) &&
                    $assignee->isEnabled() &&
                    !in_array($assignee->getId(), $listed_user_ids))
                {
                    $userlist[$assignee->getId()] = $ae->formatName($assignee->getFirstname(), $assignee->getLastname());
                    $user_counts['assignees']++;
                    $listed_user_ids[] = $assignee->getId();
                }
            }
            // finally we list all active users
            $users = $em->getRepository('JCSGYKAdminBundle:User')
                ->findBy(['enabled' => 1, 'companyId' => $company_id], ['lastname' => 'ASC', 'firstname' => 'ASC']);

            foreach ($users as $user) {
                // superadmins should not appear on the list
                if (!in_array($user->getId(), $listed_user_ids) && !$user->hasRole('ROLE_SUPER_ADMIN')) {
                    $userlist[$user->getId()] = $ae->formatName($user->getFirstname(), $user->getLastname());
                    $user_counts['all']++;
                    $listed_user_ids[] = $user->getId();
                }
            }

            // make the form
            $form = $this->createFormBuilder()
                ->add('userlist', 'choice', [
                    'label' => '',
                    'choices' => $userlist,
                    'expanded' => true,
                    'multiple' => false,
                ])
                ->getForm();

            // save the visit
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $user= $this->get('security.context')->getToken()->getUser();

                    $assignee = $em->getRepository("JCSGYKAdminBundle:User")->find($data['userlist']);

                    $this->saveVisitTask($client, $assignee, $user);

                    $this->get('session')->setFlash('notice', 'Megkeresés elmentve');

                    return $this->render('JCSGYKAdminBundle:Dialog:visit.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Dialog:visit.html.twig', ['client' => $client, 'form' => $form->createView(), 'user_counts' => $user_counts]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    private function saveVisitTask($client, $assignee, $user)
    {
        $em = $this->getDoctrine()->getManager();

        $task = new Task();
        $task->setAssignee($assignee);
        $task->setCreator($user);
        $task->setClient($client);
        $task->setType(Task::TYPE_VISIT);

        $em->persist($task);
        $em->flush();

        // save the stats
        $this->get('jcs.stat')->save(Stat::TYPE_FAMILY_HELP, 1, $assignee->getId());
    }

    /**
     * Edits the client data
     *
     * @Secure(roles="ROLE_USER")
     */
    public function editAction($id = null)
    {
        $request = $this->getRequest();

        // TODO: utca adatbázis + ellenőrzés

        $client = null;
        $em = $this->getDoctrine()->getManager();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();

        if (!empty($id)) {
            // get the client data
            $client = $this->getClient($id);
        }
        else {
            // new client
            $client = new Client();

            // family help and child welfare users get the case admin set automatically
            if ($sec->isGranted('ROLE_FAMILY_HELP') || $sec->isGranted('ROLE_CHILD_WELFARE')) {
                $client->setCaseAdmin($user);
            }
        }

        if (!empty($client)) {
            $sec = $this->get('security.context');
            // see if this user is allowed to edit - if not we simply redirect her to the view page
            if (!empty($id) && !$client->canEdit($sec)) {
                return $this->redirect($this->generateUrl('client_view', ['id' => $id]));
            }

            $form = $this->createForm(new ClientType($this->container->get('jcs.ds')), $client);

            // save the user
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    // set modifier user
                    $client->setModifier($user);

                    // save the new user data
                    if (is_null($client->getId())) {
                        // set the creator
                        $client->setCreator($user);
                        $client->setCompanyId($company_id);
                        $client->setIsArchived(false);

                        // set the client type if there is only 1 (otherwise the form will set this)
                        $client_types = $this->container->get('jcs.ds')->getClientTypes();
                        if (count($client_types) == 1) {
                            $client->setType(key($client_types));
                        }

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

                    if (empty($id)) {
                        // create a new visit task for the new client
                        $this->saveVisitTask($client, $client->getCaseAdmin(), $user);
                    }

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

    /**
     * Archive clients
     *
     * @Secure(roles="ROLE_ADMIN")
     */
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


    /**
     * View client details
     *
     * @Secure(roles="ROLE_USER")
     */
    public function viewAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);
        }
        if (!empty($client)) {
            $sec = $this->get('security.context');

            return $this->render('JCSGYKAdminBundle:Client:view.html.twig', ['client' => $client, 'problems' => $problems, 'can_edit' => $client->canEdit($sec)]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Get only the problem list of the client.
     * Used with the refreshProblems action
     *
     * @Secure(roles="ROLE_USER")
     */
    public function problemsAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($id);
            $sec = $this->get('security.context');

            return $this->render('JCSGYKAdminBundle:Client:_problems.html.twig', ['client' => $client, 'problems' => $problems, 'can_edit' => $client->canEdit($sec)]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Get the client data
     * @param int $id client id
     * @return Client
     */
    protected function getClient($id)
    {
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
            ->findOneBy(['id' => $id, 'companyId' => $company_id]);
    }

    /**
     * Client search
     * @param string $q search string
     *
     * @Secure(roles="ROLE_USER")
     */
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
}