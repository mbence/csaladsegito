<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Form\Type\ProblemType;

class ProblemController extends Controller
{
    public function viewAction($id, Request $request)
    {
        if (!empty($id)) {
            // get problem data
            $problem = $this->getProblem($id);

            return $this->render('JCSGYKAdminBundle:Problem:view.html.twig', ['client' => $problem->getClient(), 'problem' => $problem]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

    protected function getProblem($id)
    {
        return $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Problem')->find($id);
    }

    /**
     * Edits the problem
     */
    public function editAction($id = null, $client_id = null, Request $request)
    {
        $problem = null;
        $client = null;
        if (!empty($client_id)) {
            // get client data
            $company_id = $this->container->get('jcs.ds')->getCompanyId();
            $client = $this->getDoctrine()->getRepository('JCSGYKAdminBundle:Client')
                ->findOneBy(['id' => $client_id, 'companyId' => $company_id]);
        }

        $em = $this->getDoctrine()->getManager();

        if (!empty($id)) {
            // get the problem data
            $problem = $this->getProblem($id);
        }
        elseif (!empty($client)) {
            // new problem
            $problem = new Problem();
            $problem->setIsActive(true);
        }

        if (!empty($problem)) {
            if (!$problem->getIsActive()) {
                return $this->render('JCSGYKAdminBundle:Problem:view.html.twig', ['problem' => $problem]);
            }

            $form = $this->createForm(new ProblemType($this->container->get('jcs.ds')), $problem);

            // save the user
            if ($request->isMethod('POST')) {
                $form->bind($request);

                if ($form->isValid()) {

                    $user= $this->get('security.context')->getToken()->getUser();
                    // set modifier user
                    $problem->setModifier($user);

                    // save the new user data
                    if (is_null($problem->getId())) {
                        $problem->setClient($client);
                        // set the creator
                        $problem->setCreator($user);
                        $problem->setIsActive(true);
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

                    $em->flush();

                    $this->get('session')->setFlash('notice', 'Probléma elmentve');

                    //return $this->redirect($this->generateUrl('problem_edit', ['id' => $problem->getId(), 'client_id' => $client->getId()]));
                    return $this->redirect($this->generateUrl('problem_view', ['id' => $problem->getId()]));
                }
            }

            return $this->render('JCSGYKAdminBundle:Problem:edit.html.twig', ['client' => $client, 'problem' => $problem, 'form' => $form->createView()]);
        }
        else {
            throw new HttpException(400, "Bad request");
        }
    }

}