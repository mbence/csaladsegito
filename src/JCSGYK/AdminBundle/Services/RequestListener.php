<?php

namespace JCSGYK\AdminBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    private $doctrine;

    public function __construct(Doctrine $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            // return immediately
            return;
        }

        $params = $this->doctrine->getManager()
            ->getRepository('JCSGYKAdminBundle:Parameter')
            ->getList();

        $event->getRequest()->attributes->set('db.params', $params);
    }
}