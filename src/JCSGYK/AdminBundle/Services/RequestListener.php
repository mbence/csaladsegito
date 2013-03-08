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

    /**
     * Loads the company data from the db, based on the server host
     * Saves the company arra in request attributes company
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @throws HttpException 500 on faliure
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            // return immediately
            //return;
        }
    }
}