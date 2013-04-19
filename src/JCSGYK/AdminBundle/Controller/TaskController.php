<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Task;

class TaskController extends Controller
{
    public function visitsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();
        $task_status = $this->container->getParameter('task_status');

        $tasks = $em->getRepository("JCSGYKAdminBundle:Task")->findBy(['type' => Task::TYPE_VISIT, 'assignee' => $user], ['createdAt' => 'DESC']);

        return $this->render('JCSGYKAdminBundle:Task:visits.html.twig', ['tasks' => $tasks, 'task_status' => $task_status]);
    }

    public function startAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();

        $task = $em->getRepository("JCSGYKAdminBundle:Task")->findOneBy(['id' => $id, 'assignee' => $user]);

        if (empty($task)) {
            throw new HttpException(400, "Bad request");
        }

        // set the status to in progress
        $task->setStatus(Task::STATUS_STARTED);
        $em->flush();

        $client_id = $task->getClient() ? $task->getClient()->getId() : null;
        $problem_id = $task->getProblem() ? $task->getProblem()->getId() : null;

        return $this->redirect($this->generateUrl('clients', ['client_id' => $client_id, 'problem_id' => $problem_id]));
    }
}
