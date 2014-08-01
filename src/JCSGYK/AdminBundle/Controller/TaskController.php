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
     * Displays tasks of the given type
     *
     * @param int $type Task type
     *
     * @Secure(roles="ROLE_USER")
     */
    public function listAction($type)
    {
        $em          = $this->getDoctrine()->getManager();
        $sec         = $this->get('security.context');
        $user        = $sec->getToken()->getUser();
        // list of task status
        $task_status = $this->container->getParameter('task_status');

        $tasks = $em->getRepository("JCSGYKAdminBundle:Task")->getList($type, $sec);

        if ($type == Task::TYPE_VISIT || $type == Task::TYPE_DISPATCH) {
            // Display a list of TYPE_VISIT tasks

            return $this->render('JCSGYKAdminBundle:Task:visits.html.twig', ['tasks' => $tasks, 'task_status' => $task_status, 'type' => $type]);
        }
        elseif ($type == Task::TYPE_CLOSE) {
            // Display a list of problems waiting for confirmation (TYPE_CLOSE)
            // ROLE_ADMIN users see all problems and they also get a link to confirm
            // ROLE_USER level users see only their own closed prolblems, that are waiting for confirmation

            // only ROLE_ADMIN users should have any action at this list
            $readonly = !$sec->isGranted('ROLE_ADMIN');

            return $this->render('JCSGYKAdminBundle:Task:confirm.html.twig', ['tasks' => $tasks, 'task_status' => $task_status, 'readonly' => $readonly]);
        }
    }


    /**
     * Set the task status to STATUS_STARTED and taks ownership if no assignee
     * @param int $id Task id
     */
    public function startAction($id)
    {
        $ds = $this->get('jcs.ds');
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
        $client_type = $task->getClient() ? $task->getClient()->getType() : null;

        return $this->redirect($this->generateUrl('clients', ['client_id' => $client_id, 'client_type' => $ds->getSlugFromClientType($client_type), 'problem_id' => $problem_id]));
    }

    /**
     * List the incomplete client records
     * 
     * @return type
     */
    public function incompleteAction()
    {
        $clients = [];
        $ds      = $this->container->get('jcs.ds');
        $ae      = $this->container->get('jcs.twig.adminextension');
        $em      = $this->container->get('doctrine')->getManager();
        $sec     = $this->container->get('security.context');
        $user    = $sec->getToken()->getUser();

        // get the recommended fields
        $all_rec_fields = $ds->getOption('recommended_fields');
        // get the client types
        $client_types = array_keys($ds->getClientTypes());
        // build the query

        $case_admin = '';
        if (!$sec->isGranted('ROLE_ADMIN')) {
            $case_admin = "c.caseAdmin = '{$user->getId()}' AND";
        }

        $fields = [];
        foreach ($client_types as $ct) {
            $tmp = [];
            if (!empty($all_rec_fields[$ct])) {
                foreach ($all_rec_fields[$ct] as $field) {
                    $fn = $ae->toCamelCase($field);
                    $tmp[] = "c.{$fn} = ''";
                }
            }
            // only if we have some recommended fields
            if (!empty($tmp)) {
                $fields[] = "c.type = '{$ct}' AND (" . implode(' OR ', $tmp) . ')';
            }
        }

        // if no fields are recommended, then we skip the query
        if (!empty($fields)) {
            $fields = implode(') OR (', $fields);

            $clients = $em->createQuery("SELECT c, u, a FROM JCSGYKAdminBundle:Client c LEFT JOIN c.caseAdmin u LEFT JOIN c.catering a WHERE {$case_admin} ({$fields}) ORDER BY c.createdAt DESC")
                ->setMaxResults(100)
                ->getResult();
        }

        return $this->render('JCSGYKAdminBundle:Task:incomplete.html.twig', ['clients' => $clients, 'readonly' => false]);
    }
}
