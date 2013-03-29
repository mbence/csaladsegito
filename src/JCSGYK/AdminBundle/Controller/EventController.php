<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Event;
use JCSGYK\AdminBundle\Form\Type\EventType;

class EventController extends Controller
{
    public function viewAction($id, Request $request)
    {
        if (!empty($id)) {
            $event = $this->getEvent($id);

            return $this->render('JCSGYKAdminBundle:Event:view.html.twig', ['event' => $event]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    protected function getEvent($id)
    {
        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Event')->find($id);
    }

    /**
     * Edits the event
     */
    public function editAction($id = null, $problem_id = null, Request $request)
    {
        $event = null;
        $problem = null;
        if (!empty($problem_id)) {
            // get problem
            $problem = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->find($problem_id);
        }

        $em = $this->getDoctrine()->getManager();

        if (!empty($id)) {
            // get the event
            $event = $this->getEvent($id);
        }
        elseif (!empty($problem)) {
            // new problem
            $event = new Event();
            $event->setEventDate(new \DateTime());
            $event->setClientVisit(true);
        }

        if (!empty($event)) {
            if (!empty($problem) && !$problem->getIsActive()) {
                return $this->render('JCSGYKAdminBundle:Event:view.html.twig', ['event' => $event]);
            }

            $form = $this->createForm(new EventType($this->container->get('jcs.ds')), $event);

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
                    }

                    $em->flush();

                    $this->get('session')->setFlash('notice', 'Esemény elmentve');

                    //return $this->redirect($this->generateUrl('event_edit', ['id' => $event->getId(), 'problem_id' => $problem->getId()]));
                    return $this->redirect($this->generateUrl('event_view', ['id' => $event->getId()]));
                }
            }

            return $this->render('JCSGYKAdminBundle:Event:edit.html.twig', ['event' => $event, 'problem' => $problem, 'form' => $form->createView()]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

}