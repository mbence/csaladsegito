<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Entity\Event;

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
     */
    public function historyAction($id)
    {
        if (!empty($id)) {
            $client = $this->getClient($id);
        }
        if (!empty($client)) {

            $data = $this->getHistoryData($client);

            $send = $this->container->get('jcs.docx')->show(2, $data);

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

    protected function getClient($id)
    {
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        // get client data

        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
            ->findOneBy(['id' => $id, 'companyId' => $company_id]);
    }

    protected function getProblem($id)
    {
        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')
            ->findOneBy(['id' => $id, 'isDeleted' => 0]);
    }

/**
 * Print the list of templates or generate a specific document
 *
 * The generated docx file will be sent back as a downloadable file
 *
 * Uses the OpenTBS library with the OpenTBSBundle / jcs.docx service
 *
 * @param int $id Problem ID
 */
    public function makeAction($id = null)
    {
        $request = $this->getRequest();

        if (!empty($id)) {
            // get the client
            $problem = $this->getProblem($id);
            if (empty($problem)) {
                throw new HttpException(400, "Bad request");
            }

            // check user rights
            $this->canEdit($problem);

            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            $em = $this->getDoctrine()->getManager();

            $form = $this->createFormBuilder(['auto_event' => true])
                ->add('template', 'choice', [
                    'label' => '',
                    'choices' => $em->getRepository('JCSGYKAdminBundle:Template')->getTemplateList($company_id),
                    'expanded' => true,
                    'multiple' => false,
                ])
                ->add('auto_event', 'checkbox', [
                    'label' => 'Automatikus esemény létrehozása',
                ])
                ->getForm();

            // save
            if ($request->query->get('form')) {
                $form->bind($request);

                if ($form->isValid()) {
                    $form_data = $form->getData();

                    // create the auto event
                    if (!empty($form_data['auto_event'])) {
                        $user= $this->get('security.context')->getToken()->getUser();
                        // get the template name
                        $template = $em->getRepository('JCSGYKAdminBundle:Template')->find($form_data['template']);

                        $event = new Event();
                        $event->setEventDate(new \DateTime());
                        $event->setProblem($problem);
                        $event->setDescription('Nyomtatvány generálás: ' . $template->getName());
                        $event->setCreator($user);
                        $event->setType(94); // Nyomtatvány generálás

                        $em->persist($event);
                        $em->flush();
                    }

                    // generate the selected document
                    if (1 == $form_data['template']) {
                        // ACST kérelem
                        $data = $this->getDebtData($problem);
                    }
                    elseif (2 == $form_data['template']) {
                        // Esettörténet
                        $data = $this->getHistoryData($problem->getClient(), $problem);
                    }
                    else {
                        $data = [
                            'client' => $problem->getClient(),
                            'problem' => $problem
                        ];
                    }

                    $send = $this->container->get('jcs.docx')->show($form_data['template'], $data);

                    if (!$send) {
                        throw new HttpException(400, "Bad request");
                    }

                    exit;
                }
            }

            return $this->render('JCSGYKAdminBundle:Dialog:templates.html.twig', [
                'problem' => $problem,
                'form' => $form->createView(),
            ]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    private function getHistoryData(Client $client, Problem $problem = null)
    {
        $blocks = [];

        if (is_null($problem)) {
            // get all problems
            $problems = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')->getProblemList($client->getId(), 'ASC');
        }
        else {
            $problems = [$problem];
        }

        if (!empty($problems)) {

            $blocks['problem'] = [];

            // get events
            // we cant use the Doctrine relation to get the events, because we only need undeleted events and in ascending order
            $problem_repo = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem');
            $events = [];
            foreach ($problems as $problem) {
                $blocks['problem'][] = [
                    'problem' => $problem,
                    'events' => $problem_repo->getEventList($problem->getId(), 'ASC')
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
     * @param \JCSGYK\AdminBundle\Entity\Problem $problem
     * @return array
     */
    private function getDebtData(Problem $problem)
    {
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
            'client' => $problem->getClient(),
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
}