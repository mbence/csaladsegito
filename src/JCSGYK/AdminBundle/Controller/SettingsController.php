<?php

namespace JCSGYK\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JCSGYK\AdminBundle\Entity\Inquiry;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

class SettingsController extends Controller
{
    /**
    * @Secure(roles="ROLE_USER")
    */
    public function indexAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->container->get('fos_user.change_password.form');
        $formHandler = $this->container->get('fos_user.change_password.form.handler');

        $process = $formHandler->process($user);
        if ($process) {
            $this->get('session')->setFlash('notice', 'Jelszó sikeresen megváltoztatva');

            return $this->redirect($this->generateUrl('settings'));
        }

        return $this->render('JCSGYKAdminBundle:Settings:password.html.twig', ['form' => $form->createView()]);
    }
}