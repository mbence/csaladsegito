<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

class TaskController extends Controller
{
    public function visitsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();

        $tasks = $em->getRepository("JCSGYKAdminBundle:Task")->findBy(['type' => 1, 'assignee' => $user], ['createdAt' => 'DESC']);

        // generate the task urls
        foreach ($tasks as $tk => $task) {
            $client_id = $task->getClient() ? $task->getClient()->getId() : null;
            $problem_id = $task->getProblem() ? $task->getProblem()->getId() : null;
            $tasks[$tk]->setUrl($this->generateUrl('clients', ['client_id' => $client_id, 'problem_id' => $problem_id]));
        }

        return $this->render('JCSGYKAdminBundle:Task:visits.html.twig', ['tasks' => $tasks]);
    }
}
