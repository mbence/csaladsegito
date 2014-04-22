<?php

namespace JCSGYK\AdminBundle\Services;

use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use JCSGYK\AdminBundle\Services\DataStore;

class RequestListener
{
    /** DataStore */
    private $ds;

    public function __construct(DataStore $ds)
    {
        $this->ds = $ds;
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
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            // return immediately
            return;
        }

        $request = $event->getRequest();

        // check the client type slug
        // and replace it  with it's numeric constants
        if ($request->attributes->has('client_type')) {
            $slug = $request->attributes->get('client_type');
            $ct = $this->ds->getClientTypeFromSlug($slug);

            // check for a valid response
            if ($ct === false) {
                // some invalid client type received, throw an exception!

            }

            // all is fine, replace and finish
            $request->attributes->set('client_type', $ct);
        }
    }
}