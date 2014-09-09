<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use JCSGYK\AdminBundle\Entity\Event;
use JCSGYK\AdminBundle\Form\Type\EventType;
use JCSGYK\AdminBundle\Entity\Task;
use JCSGYK\AdminBundle\Entity\Stat;

class EventController extends Controller
{
    /**
     * Display event deatails
     *
     *
     */
    public function viewAction($id)
    {
        // removed temporary @PreAuthorize("hasRole('ROLE_FAMILY_HELP') or hasRole('ROLE_CHILD_WELFARE')")
        if (!empty($id)) {
            $event = $this->getEvent($id);
        }
        if (!empty($event)) {
            $client_type = $event->getProblem()->getClient()->getType();

            return $this->render('JCSGYKAdminBundle:Event:view.html.twig', [
                'event'       => $event,
                'client_type' => $client_type,
                'logs'        => $this->container->get('history.logger')->getLogs($event),
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    protected function getEvent($id)
    {
        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Event')
            ->findOneBy(['id' => $id, 'isDeleted' => 0]);
    }

    /**
     * Edits the event
     *
     * @PreAuthorize("hasRole('ROLE_FAMILY_HELP') or hasRole('ROLE_CHILD_WELFARE') or hasRole('ROLE_CATERING')")
     */
    public function editAction($id = null, $problem_id = null)
    {
        $request = $this->getRequest();

        $event = null;
        $problem = null;
        if (!empty($problem_id)) {
            // get problem
            $problem = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->find($problem_id);
        }
        $client = $problem->getClient();

        $em = $this->getDoctrine()->getManager();

        if (!empty($id)) {
            // get the event
            $event = $this->getEvent($id);
        }
        elseif (!empty($problem)) {
            // new Event
            $event = new Event();
            $event->setEventDate(new \DateTime());
            $event->setClientVisit(true);
        }

        if (!empty($event)) {
            // check user rights
            if (!empty($id)) {
                // edit existing event
                $this->canEdit($event);
            }

            $form = $this->createForm(new EventType($this->container->get('jcs.ds'), $event, $client->getType()), $event);

            // save the user
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {

                    $user= $this->get('security.context')->getToken()->getUser();
                    // set modifier user
                    $event->setModifier($user);

                    // save the new event data
                    if (is_null($event->getId())) {
                        $event->setProblem($problem);
                        // set the creator
                        $event->setCreator($user);

                        $em->persist($event);

                        // check for any pending visit task regarding this user and client, and mark it done if found
                        $tasks = $em->getRepository("JCSGYKAdminBundle:Task")->findBy(['assignee' => $user, 'client' => $client]);
                        if (!empty($tasks)) {
                            foreach ($tasks as $tk => $task) {
                                $tasks[$tk]->setStatus(Task::STATUS_DONE);
                            }
                        }

                        // save the stats withoout flusing doctrine (for the history)
                        $this->get('jcs.stat')->save(Stat::TYPE_FAMILY_HELP, 2, null, false);
                    }

                    // save the parameters
                    $pgroups = $this->container->get('jcs.ds')->getParamGroup(3, false, $client->getType());
                    $param_data = [];
                    foreach ($pgroups as $param) {
                        $param_data[$param->getId()] = $form->get('param_' . $param->getId())->getData();
                    }
                    $event->setParams($param_data);

                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Esemény elmentve');

                    //return $this->redirect($this->generateUrl('event_edit', ['id' => $event->getId(), 'problem_id' => $problem->getId()]));
                    return $this->redirect($this->generateUrl('event_view', ['id' => $event->getId()]));
                }
            }

            return $this->render('JCSGYKAdminBundle:Event:edit.html.twig', [
                'event'   => $event,
                'problem' => $problem,
                'form'    => $form->createView(),
                'logs'    => $this->container->get('history.logger')->getLogs($event),
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Delete an event
     *
     * @PreAuthorize("hasRole('ROLE_FAMILY_HELP') or hasRole('ROLE_CHILD_WELFARE') or hasRole('ROLE_CATERING')")
     */
    public function deleteAction($id)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            // get the event
            $event = $this->getEvent($id);
            if (empty($event)) {
                throw new HttpException(400, "Bad request");
            }

            // check user rights
            $this->canEdit($event);

            $form = $this->createFormBuilder()->getForm();

            // save
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $user= $this->get('security.context')->getToken()->getUser();

                    // set modifier user
                    $event->setModifier($user);
                    $event->setIsDeleted(true);

                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Esemény törölve');

                    return $this->render('JCSGYKAdminBundle:Dialog:event_delete.html.twig', [
                        'success' => true,
                    ]);
                }
            }

            return $this->render('JCSGYKAdminBundle:Dialog:event_delete.html.twig', [
                'event' => $event,
                'form' => $form->createView(),
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    /**
     * Check if this user is allowed to edit - if not we throw an exception
     * @param \JCSGYK\AdminBundle\Entity\Event $event
     * @return true on success
     */
    private function canEdit(Event $event)
    {
        $sec = $this->get('security.context');
        if (!$event->canEdit($sec)) {

            throw new AccessDeniedException();
        }

        return true;
    }
}