<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Form\Type\ClientType;
use JCSGYK\AdminBundle\Entity\Archive;
use JCSGYK\AdminBundle\Form\Type\ArchiveType;
use JCSGYK\AdminBundle\Entity\Task;
use JCSGYK\AdminBundle\Entity\Stat;
use JCSGYK\AdminBundle\Entity\Relation;
use JCSGYK\AdminBundle\Form\Type\ParentType;

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
            // get the dispatch paramGroup
            $dispatch_list = $this->container->get('jcs.ds')->getGroup(8);

            // make the form
            $form_builder = $this->createFormBuilder()
                ->add('userlist', 'choice', [
                    'label' => '',
                    'choices' => $userlist,
                    'expanded' => true,
                    'multiple' => false,
                ]);
            if (!empty($dispatch_list)) {
                // get group label
                $pgs = $this->container->get('jcs.ds')->getParamGroup(0);
                $pg_label = '';
                foreach ($pgs as $pg) {
                    if ($pg->getId() == 8) {
                        $pg_label = $pg->getName();
                        break;
                    }
                }
                $form_builder->add('dispatch', 'choice', [
                    'label' => $pg_label,
                    'choices' => $dispatch_list,
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                ]);
            }
            $form = $form_builder->getForm();

            // save the visit
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $user= $this->get('security.context')->getToken()->getUser();

                    $assignee = $em->getRepository("JCSGYKAdminBundle:User")->find($data['userlist']);
                    $dispatch = !empty($data['dispatch']) ? $data['dispatch'] : null;

                    $this->saveVisitTask($client, $assignee, $user, $dispatch);

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

    private function saveVisitTask($client, $assignee, $user, $dispatch = null)
    {
        $em = $this->getDoctrine()->getManager();
        $type = is_null($dispatch) ? Task::TYPE_VISIT : Task::TYPE_DISPATCH;

        $task = new Task();
        $task->setAssignee($assignee);
        $task->setCreator($user);
        $task->setClient($client);
        $task->setType($type);
        $task->setDispatch($dispatch);
        $em->persist($task);
        $em->flush();

        // save the stats
        $this->get('jcs.stat')->save(Stat::TYPE_FAMILY_HELP, 1, $assignee->getId());
    }

    public function parentEditAction($id, $type)
    {
        $request = $this->getRequest();
        $user= $this->get('security.context')->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        $client = $this->getClient($id);
        $parent = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id, $type);

        if (empty($parent[0])) {
            $parent = new Relation();
            $parent->setType($type);
            $parent->setChildId($id);
            $new_client = new Client($this->container->get('jcs.ds'));
            $new_client->setCompanyId($company_id);
            $new_client->setType(Client::PARENT);
            $new_client->setCreator($user);
            $new_client->setIsArchived(false);
            // set gender
            if (Relation::MOTHER == $type) {
                $new_client->setGender(2);
            } elseif (Relation::FATHER == $type) {
                $new_client->setGender(1);
            }
            // set case admin and numbers
            $new_client->setCaseYear($client->getCaseYear());
            $new_client->setCaseNumber($client->getCaseNumber());
            $new_client->setCaseAdmin($client->getCaseAdmin());
            // set the visible case number
            $new_client->setCaseLabel($this->container->get('jcs.twig.adminextension')->formatCaseNumber($client));

            $parent->setParent($new_client);
        }
        else {
            $parent = $parent[0];
        }

        if (empty($parent)) {
            throw new HttpException(400, "Bad request");
        }

        $form = $this->createForm(new ParentType($this->container->get('jcs.ds')), $parent->getParent());

        // save
        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // set modifier user
                $parent->getParent()->setModifier($user);
                $parent->getParent()->setModifiedAt(new \DateTime());

                if (is_null($parent->getId())) {
                    $em->persist($parent->getParent());
                    $em->persist($parent);
                }
                // if its a mother, we must update any related client record too
                if ($parent->getId() && $parent->getType() == Relation::MOTHER) {
                    // get the related clients
                    $mother = $parent->getParent();
                    $siblings = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getChildren($mother);
                    // update the mothers name fields
                    foreach ($siblings as $sibling_rel) {
                        $sibling = $this->getClient($sibling_rel->getChildId());
                        $sibling->setMotherTitle($mother->getTitle());
                        $sibling->setMotherFirstname($mother->getFirstname());
                        $sibling->setMotherLastname($mother->getLastname());
                    }
                }

                $em->flush();

                $this->get('session')->setFlash('notice', 'Szülő elmentve');

                return $this->render('JCSGYKAdminBundle:Dialog:parent.html.twig', [
                    'success' => true
                ]);
            }
        }
        return $this->render('JCSGYKAdminBundle:Dialog:parent.html.twig', [
            'form' => $form->createView(),
            'parent' => $parent,
        ]);
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
        $co = $this->container->get('jcs.ds')->getCompany();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();
        $client_types = $this->container->get('jcs.ds')->getClientTypes();

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
            reset($client_types);
            $client->setType(key($client_types));
        }

        if (!empty($client)) {
            $sec = $this->get('security.context');
            // see if this user is allowed to edit - if not we simply redirect her to the view page
            if (!empty($id) && !$client->canEdit($sec)) {
                return $this->redirect($this->generateUrl('client_view', ['id' => $id]));
            }

            // get the parents for CHILD WELFARE
            $parents = [];
            $relation_types = [];
            if ($client->getId() && $client->getType() == Client::CW) {
                $parents = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id);
                $relation_types = $this->container->get('jcs.ds')->getRelationTypes();
                foreach ($parents as $parent) {
                    unset($relation_types[$parent->getType()]);
                }
            }

            $form = $this->createForm(new ClientType($this->container->get('jcs.ds'), $client), $client);

            $orig_year = $client->getCaseYear();
            $orig_casenum = $client->getCaseNumber();

            // save the user
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    // set modifier user
                    $client->setModifier($user);
                    $client->setModifiedAt(new \DateTime());
                    $case_num = $client->getCaseNumber();

                    // save the new user data
                    if (is_null($client->getId())) {
                        if (empty($case_num)) {
                            // if not defined, get the next case number from the ClientSequence Service
                            // also checks for year changes and resets the sequence for a new year if needed
                            $nextVal = $this->get('jcs.seq')->nextVal($co);
                            if (false === $nextVal) {
                                // this is really bad
                                throw new HttpException(500);
                            }

                            $client->setCaseYear($nextVal['year']);
                            $client->setCaseNumber($nextVal['id']);
                        }
                        else {
                            $copy_case = true;
                        }

                        // set the creator
                        $client->setCreator($user);
                        $client->setCompanyId($company_id);
                        $client->setIsArchived(false);

                        // set the client type if there is only 1 (otherwise the form will set this)
                        if (count($client_types) == 1) {
                            $client->setType(key($client_types));
                        }

                        $em->persist($client);
                        $em->flush();

                        // if case number given, we must copy over a few fields from that case
                        if (!empty($copy_case)) {
                            $this->copyCaseData($client);
                        }
                    }
                    // restore the case number and year
                    elseif (empty($case_num)) {
                        $client->setCaseYear($orig_year);
                        $client->setCaseNumber($orig_casenum);
                    }

                    // set the visible case number
                    $client->setCaseLabel($this->container->get('jcs.twig.adminextension')->formatCaseNumber($client));

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

                    // handle/save the addresses
                    foreach ($client->getAddresses() as $adr) {
                        $val = $adr->getCity() . $adr->getStreet();
                        if (empty($val)) {
                            // remove the empty address
                            $client->removeAddress($adr);
                            $em->remove($adr);
                        }
                        else {
                            $aid = $adr->getId();
                            if (empty($aid)) {
                                // set the client id
                                $adr->setClient($client);
                                $adr->setCreator($user);
                                $em->persist($adr);
                            }
                            else {
                                $adr->setModifier($user);
                            }
                        }
                    }

                    // copy the mothers name from the relatives record
                    $mother = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($client->getId(), Relation::MOTHER);
                    if (!empty($mother[0])) {
                        $mother = $mother[0]->getParent();
                        $client->setMotherTitle($mother->getTitle());
                        $client->setMotherFirstname($mother->getFirstname());
                        $client->setMotherLastname($mother->getLastname());
                    }

                    // save the parameters
                    $pgroups = $this->container->get('jcs.ds')->getParamGroup(1);
                    $param_data = [];
                    foreach ($pgroups as $param) {
                        $param_data[$param->getId()] = $form->get('param_' . $param->getId())->getData();
                    }
                    $client->setParams($param_data);

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

            return $this->render('JCSGYKAdminBundle:Client:edit.html.twig', [
                'client' => $client,
                'problems' => $problems,
                'form' => $form->createView(),
                'parents' => $parents,
                'new_relations' => $relation_types,
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Copies over a few fields from the given case
     *
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     */
    private function copyCaseData(Client &$client)
    {
        if ($client->getCaseNumber() && $client->getCaseYear()) {
            $em = $this->getDoctrine()->getManager();
            // find the case
            $case = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getCase($client);

            if (!empty($case[0])) {
                $sibling = $case[0];

                // case admin
                $client->setCaseAdmin($sibling->getCaseAdmin());

                // mothers data
                $this->copyFields($client, $sibling, ['MotherTitle', 'MotherFirstname', 'MotherLastname']);

                // save the location data if empty
                if (!$client->getCity() || !$client->getStreet()) {
                    $this->copyFields($client, $sibling, ['Country', 'ZipCode', 'City', 'Street', 'StreetType', 'StreetNumber', 'FlatNumber']);
                }
                if (!$client->getLocationCity() || !$client->getLocationStreet()) {
                    $this->copyFields($client, $sibling, ['LocationCountry', 'LocationZipCode', 'LocationCity', 'LocationStreet', 'LocationStreetType', 'LocationStreetNumber', 'LocationFlatNumber']);
                }
                // clone the last address of the case
                $addresses = $sibling->getAddresses();
                $act_addr = $addresses->last();
                if (!empty($act_addr)) {
                    $new_addr = clone $act_addr;
                    $new_addr->setClient($client);
                    $em->persist($new_addr);
                }

                // copy over the relations
                $rels = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($sibling->getId());
                foreach ($rels as $rel) {
                    $parent = new Relation();
                    $parent->setType($rel->getType());
                    $parent->setChildId($client->getId());
                    if ($rel->getType() == Relation::MOTHER) {
                        // we only link to the mother,
                        $parent->setParent($rel->getParent());
                    }
                    else {
                        // but clone the other relations
                        $old_parent = $rel->getParent();
                        $new_parent = clone $old_parent;
                        $em->persist($new_parent);
                        $parent->setParent($new_parent);
                    }

                    $em->persist($parent);
                }
                $em->flush();

                return true;
            }

            return false;
        }
    }

    private function copyFields(Client &$client, Client &$sibling, array $fields)
    {
        foreach ($fields as $field) {
            $setter = 'set' . $field;
            $getter = 'get' . $field;
            $client->$setter($sibling->$getter());
        }
    }

    /**
     * Archive clients
     *
     * @PreAuthorize("hasRole('ROLE_ADMIN') or hasRole('ROLE_ASSISTANCE')")
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
            $client_types = $this->container->get('jcs.ds')->getClientTypes();

            // get the parents only for CHILD WELFARE
            $parents = [];
            $relation_types = [];
            if ($client->getType() == Client::CW) {
                $parents = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id);
                $relation_types = $this->container->get('jcs.ds')->getRelationTypes();
                foreach ($parents as $parent) {
                    if (isset($relation_types[$parent->getType()])) {
                        unset($relation_types[$parent->getType()]);
                    }
                }
            }

            return $this->render('JCSGYKAdminBundle:Client:view.html.twig', [
                'client' => $client,
                'problems' => $problems,
                'can_edit' => $client->canEdit($sec),
                'display_type' => count($client_types) > 1,  // only display the client type if there are more then one types of this company
                'parents' => $parents,
                'new_relations' => $relation_types,
            ]);
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
     * Get only the parent list of the client.
     * Used with the refreshParents JS action
     *
     * @Secure(roles="ROLE_USER")
     */
    public function parentsAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
            $parents = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getRelations($id);
            $relation_types = $this->container->get('jcs.ds')->getRelationTypes();
            foreach ($parents as $parent) {
                if (isset($relation_types[$parent->getType()])) {
                    unset($relation_types[$parent->getType()]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Client:_parents.html.twig', ['client' => $client, 'parents' => $parents, 'new_relations' => $relation_types, 'edit' => true]);
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
     * Try to decide if a sting is a case number
     * @param text $q
     */
    protected function isCase($q)
    {
        $company = $this->container->get('jcs.ds')->getCompany();
        $tpl = $company['caseNumberTemplate'];
        preg_match_all('/(.*?)(\{.*?\})/', $tpl, $matches, PREG_SET_ORDER);

        $pattern = '';
        foreach ($matches as $m) {
            $pattern .= preg_quote($m[1], '/');
            $pattern .= $m[2] == '{year}' ? '\d{4}' : '\d?';
        }
        preg_match("/(*UTF8){$pattern}/i", $q, $case_matches);

        return !empty($case_matches[0]);
    }

    /**
     * Client search
     * @param string $q search string
     *
     * @Secure(roles="ROLE_USER")
     */
    public function searchAction()
    {
        $request = $this->getRequest();
        $q = $request->query->get('q');

        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $limit = 100;

        $re = [];
        $sql = '';

        // save the search string
        $this->get('session')->set('quicksearch', $q);

        $time_start = microtime(true);
        if (!empty($q)) {
            $num_ver = Client::cleanupNum($q);
            $db = $this->get('doctrine.dbal.default_connection');
            $sql = "SELECT id, type, case_year, case_number, company_id, title, firstname, lastname, mother_firstname, mother_lastname, zip_code, city, street, street_type, street_number, flat_number FROM client WHERE";
            // recognize a case number
            if ($this->isCase($q)) {
                $sql .= " case_label LIKE {$db->quote($q . '%')} AND company_id={$db->quote($company_id)} AND type IN (1,2)";
                $sql .= " ORDER BY case_label, lastname, firstname LIMIT " . $limit;
            }
            // search for ID
            elseif (is_numeric($num_ver)) {
                $sql .= " (case_number={$db->quote($num_ver)} AND company_id={$db->quote($company_id)} AND type IN (1,2)) OR (social_security_number LIKE {$db->quote($num_ver . '%')} AND company_id={$db->quote($company_id)} AND type IN (1,2))";
                $sql .= " ORDER BY lastname, firstname LIMIT " . $limit;
            }
            else {
                $search_words = explode(' ', trim($q));
                // We cant use FULLTEXT search for fields with very light weights (same values most of the times)
                // because the indexer ignores these. Street number and street types are such fields.
                // We must use HAVING after the FULLTEXT search to filter these fields.
                $first = reset($search_words);
                // if the first word is a number, then we use it as a zip code
                if (is_numeric($first)){
                    array_shift($search_words);
                    $first = $db->quote($first);
                }
                else {
                    $first = false;
                }
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

                $sql .= " MATCH (firstname, lastname, street, mother_firstname, mother_lastname) AGAINST ({$qr} IN BOOLEAN MODE)";

                $xsql = ['company_id=' . $company_id, "type IN (1,2)"];

                // if we search for street number
                if (!empty($first)) {
                    $xsql[] = "zip_code LIKE " . $first;
                }
                if (!empty($last)) {
                    $xsql[] = "street_number LIKE " . $last;
                }
                if (!empty($street_types)) {
                    $xsql[] = "street_type IN (" . implode(',', $street_types) . ")";
                }

                $sql .= " HAVING " . implode(' AND ', $xsql);
                $sql .= " ORDER BY lastname, firstname LIMIT " . $limit;
            }
            $re = $db->fetchAll($sql);
        }
        $time_end = microtime(true);
        $time = number_format(($time_end - $time_start) * 1000, 3, ',', ' ');

        return $this->render('JCSGYKAdminBundle:Client:results.html.twig', ['clients' => $re, 'time' => $time, 'sql' => $sql, 'resnum' => count($re)]);
    }
}