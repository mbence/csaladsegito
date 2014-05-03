<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JCSGYK\AdminBundle\Entity\Stat;

class ReportsController extends Controller
{
    private $reports = [];

    /**
    * @Secure(roles="ROLE_ADMIN")
    */
    public function indexAction($report = null)
    {
        $request = $this->getRequest();

        $form = $this->createFormBuilder([])->getForm();

        // render and download reports
        if ($request->isMethod('POST')) {
            

        }

        return $this->render('JCSGYKAdminBundle:Reports:index.html.twig', [
            'menu' => $this->getMenu(),
            'report' => $report,
            'form' => $form->createView(),
        ]);
    }

    private function getMenu()
    {
        $menu = [
            ['slug' => 'clients', 'label' => 'Ügyfelek', 'role' => 'ROLE_USER'],
            ['slug' => 'casecount', 'label' => 'Esetszámok', 'role' => 'ROLE_ADMIN'],
        ];

        return $menu;
    }
}