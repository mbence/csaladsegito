<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Form;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Entity\Event;
use JCSGYK\AdminBundle\Entity\DocTemplate;

class TemplateController extends Controller
{
    /**
     * Genarate a docx file with the case history of the client
     *
     * The generated docx file will be sent back as a downloadable file
     *
     * Uses the OpenTBS library with the OpenTBSBundle / jcs.docx service
     *
     * @param int $id Client ID
     * @Security("has_role('ROLE_USER')")
     * @Route("clients/history/{id}", name="client_history")
     * @return Response
     */
    public function historyAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
        }
        if (!empty($client)) {

            $data = $this->getHistoryData($client);

            $em = $this->container->get('doctrine')->getManager();
            $doc = $em->getRepository('JCSGYKAdminBundle:DocTemplate')->find(2);
            $send = $this->container->get('jcs.docx')->show($doc, $data);

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

    /**
     * Get a client
     * @param int $id client id
     * @return Client
     */
    private function getClient($id)
    {
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
            ->findOneBy(['id' => $id, 'companyId' => $company_id]);
    }

    /**
     * Get a problem
     * @param int $id problem id
     * @return Problem
     */
    private function getProblem($id)
    {
        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')
            ->findOneBy(['id' => $id, 'isDeleted' => 0]);
    }

    /**
     * Print the list of templates or generate a specific document
     * The generated docx file will be sent back as a downloadable file
     * Uses the OpenTBS library with the OpenTBSBundle / jcs.docx service
     *
     * @param Request $request
     * @param int $problem_id Problem ID
     * @param int $client_id client ID
     * @Security("has_role('ROLE_USER')")
     * @Route("/client_templates/{client_id}", name="client_templates")
     * @Route("/problem_templates/{problem_id}", name="problem_templates")
     * @Template("JCSGYKAdminBundle:Dialog:templates.html.twig")
     * @return Response
     */
    public function makeAction(Request $request, $problem_id = null, $client_id = null)
    {
        if (empty($problem_id) && empty($client_id)) {
            throw new HttpException(400, "Bad request");
        }
        $client  = null;
        $problem = null;
        $club    = null;

        // get the problem or client
        if (!empty($problem_id)) {
            $problem  = $this->getProblem($problem_id);
            $id       = $problem_id;
        } else {
            $client   = $this->getClient($client_id);
            $id       = $client_id;
        }

        // check again
        if (empty($problem) && empty($client)) {
            throw new HttpException(400, "Bad request");
        }

        // check user rights
        if (!empty($problem)) {
            $this->canEdit($problem);
        } else {
            $this->canEdit($client);
        }

        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $em         = $this->container->get('doctrine')->getManager();
        $club       = $this->findClub($client, $problem);

        // get the template names
        $template_list = $em->getRepository('JCSGYKAdminBundle:DocTemplate')->getTemplateList($company_id, !empty($client), !empty($problem), $club);

        // make the form url
        $route_options = !empty($client_id) ? ['client_id' => $client_id] : ['problem_id' => $problem_id];
        $url = $this->generateUrl($request->get('_route'), $route_options);

        $form = $this->getForm($template_list, $url, !empty($problem));
        $form->handleRequest($request);

        // we will always need the client
        if (empty($client)) {
            $client = $problem->getClient();
        }

        // save
        if ($form->isValid()) {
            $form_data = $form->getData();
            $doc = $em->getRepository('JCSGYKAdminBundle:DocTemplate')->find($form_data['template']);

            // create the auto event (only for problems)
            if (!empty($form_data['auto_event']) && !empty($problem)) {
                $this->saveEvent($doc, $problem);
            }

            // generate the selected document
            if (1 == $form_data['template']) {
                // ACST kérelem
                $data = $this->getDebtData($client, $problem);
            }
            elseif (2 == $form_data['template']) {
                // Esettörténet
                $data = $this->getHistoryData($client, $problem);
            }
            else {
                $data = [
                    'client' => $client,
                    'problem' => $problem
                ];

                // add catering record if available
                $catering = $client->getCatering();
                if (!empty($catering)) {
                    $data['catering'] = $catering;
                }
                // add homehelp record if available
                $homehelp = $client->getHomehelp();
                if (!empty($homehelp)) {
                    $data['homehelp'] = $homehelp;
                }
            }

            $send = $this->container->get('jcs.docx')->show($doc, $data);

            if (!$send) {
                throw new HttpException(400, "Bad request");
            }

            exit;
        }

        return [
            'client'  => $client,
            'problem' => $problem,
            'form'    => $form->createView(),
        ];
    }


    /**
     * Try to find the club id of the client
     * @param Client $client
     * @param Problem $problem
     * @return int club id or null when no club set
     */
    private function findClub(Client $client = null, Problem $problem = null)
    {
        if (empty($client) && !empty($problem)) {
            $client = $problem->getClient();
        }

        if (!empty($client)) {
            // try Catering
            $catering = $client->getCatering();
            if (!empty($catering)) {

                return $catering->getClub()->getId();
            }

            // try homehelp
            $homehelp = $client->getHomehelp();
            if (!empty($homehelp)) {

                return $homehelp->getClub()->getId();
            }
        }

        return null;
    }

    /**
     * Returns a form
     * @param array $template_list
     * @param int $id selected doctemplate
     * @return Form
     */
    private function getForm($template_list, $url, $add_auto_event = false)
    {
        $builder =  $this->createFormBuilder(['auto_event' => true])
            ->setAction($url)
            ->setMethod('POST')
            ->add('template', 'choice', [
                'label' => '',
                'choices' => $template_list,
                'expanded' => true,
                'multiple' => false,
            ]);
        // we only add auto events for PROBLEM type docTemplates
        if ($add_auto_event) {
            $builder->add('auto_event', 'checkbox', [
                'label' => 'Automatikus esemény létrehozása',
            ]);
        }

        return $builder->getForm();
    }

    /**
     * Saves an event to the problem
     * @param DocTemplate $doc
     * @param Problem $problem
     */
    private function saveEvent(DocTemplate $doc, Problem $problem)
    {
        $em = $this->container->get('doctrine')->getManager();
        $user= $this->get('security.context')->getToken()->getUser();

        $event = (new Event())
            ->setEventDate(new \DateTime())
            ->setProblem($problem)
            ->setDescription('Nyomtatvány generálás: ' . $doc->getName())
            ->setCreator($user)
        ;

        $em->persist($event);
        $em->flush();
    }

    /**
     * Get History Data
     * @param Client $client
     * @param Problem $problem
     * @return array
     */
    private function getHistoryData(Client $client, Problem $problem = null)
    {
        $blocks = [
            'problem' => []
        ];

        if (is_null($problem)) {
            // get all problems
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($client->getId(), 'ASC');
        }
        else {
            $problems = [$problem];
        }

        if (!empty($problems)) {
            // get events
            // we cant use the Doctrine relation to get the events, because we only need undeleted events and in ascending order
            $problem_repo = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem');

            foreach ($problems as $pro) {
                $blocks['problem'][] = [
                    'problem' => $pro,
                    'events' => $problem_repo->getEventList($pro->getId(), 'ASC')
                ];
            }
        }

        // return the field map for the template
        return [
            'client' => $client,
            'blocks' => $blocks
        ];
    }


    /**
     * finds the debts of the given problems
     *
     * @param Client $client
     * @param Problem $problem
     * @return array
     */
    private function getDebtData(Client $client, Problem $problem = null)
    {
        if (empty($problem)) {
            return [
                'client' => $client,
                'problem' => null,
                'debts' => []
            ];
        }

        $em = $this->getDoctrine()->getManager();
        // get the debt records
        $debts = $em
            ->createQuery("SELECT d FROM JCSGYKAdminBundle:Debt d WHERE d.problem=:problem")
            ->setParameter('problem', $problem)
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
            'problem' => $problem,
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

    /**
     * Check if this user is allowed to edit - if not we throw an exception
     * @param Problem|Client $problem
     * @return true on success
     */
    private function canEdit($entity)
    {
        $sec = $this->get('security.context');
        if (!$entity->canEdit($sec)) {

            throw new AccessDeniedException();
        }

        return true;
    }
}