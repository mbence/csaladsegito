<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;

use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Entity\Event;
use JCSGYK\AdminBundle\Form\Type\ProblemType;
use JCSGYK\AdminBundle\Form\Type\CloseProblemType;
use JCSGYK\AdminBundle\Entity\Task;

class ProblemController extends Controller
{
    /**
     * Show the problem details
     *
     * @Secure(roles="ROLE_USER")
     */
    public function viewAction($id)
    {
        if (!empty($id)) {
            // get problem data
            $problem = $this->getProblem($id);
            $events = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->getEventList($id);
        }
        if (!empty($problem)) {
            $sec = $this->get('security.context');

            $has_agreement = $problem->getAgreementExpiresAt() >= new \DateTime('today');

            return $this->render('JCSGYKAdminBundle:Problem:view.html.twig', [
                'client' => $problem->getClient(),
                'problem' => $problem,
                'events' => $events,
                'can_edit' => $problem->canEdit($sec),
                'has_agreement' => $has_agreement
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Read the given problem id
     * @param int $id
     * @return Problem
     */
    protected function getProblem($id)
    {
        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')
            ->findOneBy(['id' => $id, 'isDeleted' => 0]);
    }

    /**
     * Edits the problem
     *
     * @Secure(roles="ROLE_USER")
     */
    public function editAction($id = null, $client_id = null)
    {
        $request = $this->getRequest();

        $problem = null;
        $client = null;
        if (!empty($client_id)) {
            // get client
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            $client = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
                ->findOneBy(['id' => $client_id, 'companyId' => $company_id]);
        }

        $em = $this->getDoctrine()->getManager();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();

        if (!empty($id)) {
            // get the problem
            $problem = $this->getProblem($id);
        }
        elseif (!empty($client)) {
            // new problem
            $problem = new Problem();
            $problem->setIsActive(true);

            // family help and child welfare users get the assignee set automatically
            if ($sec->isGranted('ROLE_FAMILY_HELP') || $sec->isGranted('ROLE_CHILD_WELFARE')) {
                $problem->setAssignee($user);
            }
        }

        if (!empty($problem)) {
            // check user rights (thows Access Denied in failure!)
            if (empty($id)) {
                // create a new problem
                $sec = $this->get('security.context');
                if (!$client->canEdit($sec)) {
                    throw new AccessDeniedException();
                }
            }
            else {
                // edit existing problem
                $this->canEdit($problem);
            }

            $form = $this->createForm(new ProblemType($this->container->get('jcs.ds'), $problem), $problem);

            // save the problem
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    // set modifier user
                    $problem->setModifier($user);

                    // save the new user data
                    if (is_null($problem->getId())) {
                        $problem->setClient($client);
                        // set the creator
                        $problem->setCreator($user);

                        $em->persist($problem);
                    }

                    // handle/save the debts
                    foreach ($problem->getDebts() as $de) {
                        $val = $de->getManagedDebt() + $de->getRegisteredDebt();
                        if (empty($val)) {
                            // remove the empty debts
                            $problem->removeDebt($de);
                            $em->remove($de);
                        }
                        else {
                            // set the client id
                            $de->setProblem($problem);
                            // save the rest
                            $em->persist($de);
                        }
                    }

                    // save the parameters
                    $pgroups = $this->container->get('jcs.ds')->getParamGroup(2);
                    $param_data = [];
                    foreach ($pgroups as $param) {
                        $param_data[$param->getId()] = $form->get('param_' . $param->getId())->getData();
                    }
                    $problem->setParams($param_data);

                    $em->flush();

                    $this->get('session')->setFlash('notice', 'Probléma elmentve');

                    //return $this->redirect($this->generateUrl('problem_edit', ['id' => $problem->getId(), 'client_id' => $client->getId()]));
                    return $this->redirect($this->generateUrl('problem_view', ['id' => $problem->getId()]));
                }
            }
            $events = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->getEventList($id);

            return $this->render('JCSGYKAdminBundle:Problem:edit.html.twig', ['client' => $client, 'problem' => $problem, 'events' => $events, 'form' => $form->createView()]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Closes the problem
     *
     * @Secure(roles="ROLE_USER")
     */
    public function closeAction($id)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            // get the problem
            $problem = $this->getProblem($id);
            if (empty($problem)) {
                throw new HttpException(400, "Bad request");
            }

            // check user rights (thows Access Denied in failure!)
            $this->canEdit($problem);

            $form = $this->createForm(new CloseProblemType($this->container->get('jcs.ds'), $problem->getIsActive()), $problem);

            // save
            if ($request->isMethod('POST')) {
                $form->bind($request);

                $operation = $form->get('operation')->getData();
                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $user= $this->get('security.context')->getToken()->getUser();

                    if ($operation == 1) {
                        // close problem
                        $problem->setCloser($user);
                        $problem->setClosedAt(new \DateTime());

                        // create an anon task for every ROLE_ADMIN (no assignee)
                        $task = new Task();
                        $task->setCreator($user);
                        $task->setClient($problem->getClient());
                        $task->setProblem($problem);
                        $task->setType(Task::TYPE_CLOSE);

                        $em->persist($task);
                        $em->flush();
                    }
                    else {
                        // reopen the problem
                        $problem->setOpener($user);

                        // close all related tasks
                        $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Task')->closeAll($problem, Task::TYPE_CLOSE);
                    }
                    // set modifier user
                    $problem->setModifier($user);

                    // set the problem status
                    $problem->setIsActive(1 - $operation);

                    // update client record
                    $problem->getClient()->updateAgreementDate();
                    $em->flush();

                    $this->get('session')->setFlash('notice', ($operation ? 'Probléma lezárva' : 'Probléma újranyitva'));

                    return $this->render('JCSGYKAdminBundle:Dialog:problem_close.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Dialog:problem_close.html.twig', [
                'problem' => $problem,
                'client' => $problem->getClient(),
                'form' => $form->createView(),
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Get the list of events for a problem
     *
     * @Secure(roles="ROLE_USER")
     */
    public function getEventsAction($id)
    {
        if (!empty($id)) {
            $problem = $this->getProblem($id);
            $events = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->getEventList($id);
        }
        if (!empty($problem)) {
            $sec = $this->get('security.context');

            return $this->render('JCSGYKAdminBundle:Problem:_events.html.twig', [
                'problem' => $problem,
                'events' => $events,
                'can_edit' => $problem->canEdit($sec)
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Delete a problem
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function deleteAction($id)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            // get the problem
            $problem = $this->getProblem($id);
            if (empty($problem)) {
                throw new HttpException(400, "Bad request");
            }

            // check user rights (thows Access Denied in failure!)
            $this->canEdit($problem);

            $form = $this->createFormBuilder()->getForm();

            // save
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $user= $this->get('security.context')->getToken()->getUser();

                    // set modifier user
                    $problem->setModifier($user);
                    $problem->setIsDeleted(true);

                    // update client record
                    $problem->getClient()->updateAgreementDate();
                    $em->flush();

                    $this->get('session')->setFlash('notice', 'Probléma törölve');

                    return $this->render('JCSGYKAdminBundle:Dialog:problem_delete.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            $events = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->getEventList($id);

            return $this->render('JCSGYKAdminBundle:Dialog:problem_delete.html.twig', [
                'problem' => $problem,
                'form' => $form->createView(),
                'events' => $events
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Check if this user is allowed to edit - if not we throw an exception
     * @param \JCSGYK\AdminBundle\Entity\Problem $problem
     * @return true on success
     */
    private function canEdit(Problem $problem)
    {
        $sec = $this->get('security.context');
        if (!$problem->canEdit($sec)) {

            throw new AccessDeniedException();
        }

        return true;
    }

    /**
     * Set the confirmer and confirmed at fields
     *
     * @param int $id Problem id
     *
     * @Secure(roles="ROLE_ADMIN")
     */
    public function confirmAction($id)
    {
        $request = $this->getRequest();

        // get the problem
        $problem = $this->getProblem($id);
        if (empty($problem)) {
            throw new HttpException(400, "Bad request");
        }

        // check user rights (thows Access Denied in failure!)
        $this->canEdit($problem);

        $form = $this->createFormBuilder()->getForm();

        // save
        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $user= $this->get('security.context')->getToken()->getUser();

                // set modifier user
                $problem->setConfirmer($user);
                $problem->setConfirmedAt(new \DateTime);

                // close the related tasks too
                $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Task')->closeAll($problem, Task::TYPE_CLOSE);

                $em->flush();

                $this->get('session')->setFlash('notice', 'Probléma lezárás jóváhagyva');

                return $this->render('JCSGYKAdminBundle:Dialog:problem_agreement.html.twig', [
                    'success' => true,
                ]);
            }
        }

        return $this->render('JCSGYKAdminBundle:Dialog:problem_confirm.html.twig', [
            'problem' => $problem,
            'form' => $form->createView(),
        ]);
    }

/**
     * Start / stop the agreement for the actual problem
     *
     * @param int $id Problem id
     *
     * @Secure(roles="ROLE_USER")
     */
    public function agreementAction($id)
    {
        $request = $this->getRequest();

        // get the problem
        $problem = $this->getProblem($id);
        if (empty($problem)) {
            throw new HttpException(400, "Bad request");
        }

        // check user rights (thows Access Denied in failure!)
        $this->canEdit($problem);

        $has_agreement = $problem->getAgreementExpiresAt() >= new \DateTime('today');
        $exp = $problem->getAgreementExpiresAt() ? $problem->getAgreementExpiresAt() : new \DateTime;

        $defaults = [
            'operation' => $has_agreement,
            'agreement_exp_type' => !is_null($problem->getAgreementExpiresAt()),
            'agreement_expires_at' => $exp
        ];

        $form = $this->createFormBuilder($defaults)
            ->add('operation', 'hidden')
            ->add('agreement_exp_type', 'choice', [
                'expanded' => true,
                'multiple' => false,
                'choices' => ['Visszavonásig', 'Dátum: ']
            ])
            ->add('agreement_expires_at', 'date', [
                'label' => 'Megállapodás érvényes',
                'format' => 'yMMdd',
            ])
            ->getForm();

        // save
        if ($request->isMethod('POST')) {
            $form->bind($request);

            $operation = $form->get('operation')->getData();
            if ($operation != $has_agreement) {
                $msg = $has_agreement ? "A megállapodás már rögzítésre került" : "A megállapodás már törlésre került";
                $this->get('session')->setFlash('error', $msg);
            }
            elseif ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $user= $this->get('security.context')->getToken()->getUser();
                $data = $form->getData();

                // set modifier user
                $problem->setModifier($user);

                if ($has_agreement) {
                    // agreement closed
                    $problem->setAgreementExpiresAt(null);
                }
                elseif ($data['agreement_exp_type']) {
                    // defined length agreement
                    $problem->setAgreementExpiresAt($data['agreement_expires_at']);
                }
                else {
                    // undefined lenght agreement
                    $problem->setAgreementExpiresAt(new \DateTime('9999-12-31'));
                }

                $event_message = $has_agreement ? "Megállapodás vége" : "Megállapodás kezdete";
                $event_type = $has_agreement ? 96 : 95;

                // create the auto events
                $event = new Event();
                $event->setEventDate(new \DateTime());
                $event->setProblem($problem);
                $event->setDescription($event_message);
                $event->setCreator($user);
                $event->setType($event_type);

                $em->persist($event);

                // update client record
                $problem->getClient()->updateAgreementDate();
                $em->flush();

                $msg = $has_agreement ? "A megállapodás törölve" : "A megállapodás rögzítve";

                $this->get('session')->setFlash('notice', $msg);

                return $this->render('JCSGYKAdminBundle:Dialog:problem_agreement.html.twig', [
                    'success' => true,
                ]);
            }
        }

        return $this->render('JCSGYKAdminBundle:Dialog:problem_agreement.html.twig', [
            'problem' => $problem,
            'operation' => (1 - $has_agreement),
            'form' => $form->createView(),
        ]);
    }
}