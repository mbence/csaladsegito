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
    /**
     * Display a list of TYPE_VISIT tasks
     *
     * @Secure(roles="ROLE_USER")
     */
    public function visitsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();
        // list of task status
        $task_status = $this->container->getParameter('task_status');

        $tasks = $em->getRepository("JCSGYKAdminBundle:Task")->getList(Task::TYPE_VISIT, $sec);
        
        return $this->render('JCSGYKAdminBundle:Task:visits.html.twig', ['tasks' => $tasks, 'task_status' => $task_status]);
    }

    /**
     * Display a list of problems waiting for confirmation (TYPE_CLOSE)
     * ROLE_ADMIN users see all problems and they also get a link to confirm
     * ROLE_USER level users see only their own closed prolblems, that are waiting for confirmation
     *
     * @Secure(roles="ROLE_USER")
     */
    public function confirmAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();
        // list of task status
        $task_status = $this->container->getParameter('task_status');

        $tasks = $em->getRepository("JCSGYKAdminBundle:Task")->getList(Task::TYPE_CLOSE, $sec);

        // only ROLE_ADMIN users should have any action at this list
        $readonly = !$sec->isGranted('ROLE_ADMIN');

        return $this->render('JCSGYKAdminBundle:Task:confirm.html.twig', ['tasks' => $tasks, 'task_status' => $task_status, 'readonly' => $readonly]);
    }


    /**
     * Set the task status to STATUS_STARTED and taks ownership if no assignee
     * @param int $id Task id
     */
    public function startAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $sec = $this->get('security.context');
        $user= $sec->getToken()->getUser();

        $task = $em->getRepository("JCSGYKAdminBundle:Task")->findOneBy(['id' => $id]);

        if (empty($task)) {
            throw new HttpException(400, "Bad request");
        }

        // set the status to in progress
        $task->setStatus(Task::STATUS_STARTED);
        $task->setAssignee($user);

        $em->flush();

        $client_id = $task->getClient() ? $task->getClient()->getId() : null;
        $problem_id = $task->getProblem() ? $task->getProblem()->getId() : null;

        return $this->redirect($this->generateUrl('clients', ['client_id' => $client_id, 'problem_id' => $problem_id]));
    }
}
