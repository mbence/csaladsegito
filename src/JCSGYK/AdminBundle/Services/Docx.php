<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\DocTemplate;
use JCSGYK\AdminBundle\Entity\Catering;
use JCSGYK\AdminBundle\Entity\HomeHelp;

/**
 * Service for Generatinc Docx files from templates
 */
class Docx
{
    /** Service container */
    private $container;

    /** Opentbs service */
    private $tbs;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->tbs = $this->container->get('opentbs');
    }


    /**
     * Generate a file from a template, merge the fields, and send the file as a download
     * No data mapping happens
     *
     * @param string $template Template filename
     * @param array $data Array of merge fields
     */
    public function make($template_file, $data, $file_name = null)
    {
        if (empty($template_file)) {
            return false;
        }

        $tbs = $this->container->get('opentbs');
        //$tbs->SetOption('noerr', true);

        $tbs->LoadTemplate($template_file, OPENTBS_ALREADY_UTF8); // OPENTBS_DEFAULT, OPENTBS_ALREADY_UTF8, OPENTBS_ALREADY_XML

        // do the field merge
        foreach ($data as $base => $merge) {
            if ('blocks' == $base) {
                foreach ($merge as $block => $source) {
                    $tbs->MergeBlock($block, $source);
                }
            }
            else {
                $tbs->MergeField($base, $merge);
            }
        }

        if (!is_null($file_name)) {
            // send back the file
            $tbs->Show(OPENTBS_DOWNLOAD, $file_name);
        }
        else {
            // return the file contents
            $tbs->Show(OPENTBS_STRING);

            return $tbs->Source;
        }
    }

    /**
     * Generate a file from a template, merge the fields, and send the file as a download
     *
     * @param string $template Template filename
     * @param array $data Array of merge fields
     */
    public function makeReport($template_file, $data, $file_name)
    {
        $em = $this->container->get('doctrine')->getManager();

        if (empty($template_file)) {
            return false;
        }

        $tbs = $this->container->get('opentbs');
        //$tbs->SetOption('noerr', true);

        $tbs->LoadTemplate($template_file, OPENTBS_ALREADY_UTF8); // OPENTBS_DEFAULT, OPENTBS_ALREADY_UTF8, OPENTBS_ALREADY_XML

        // get the field map
        $fields = $this->getMap($data);

        // do the field merge
        foreach ($fields as $base => $merge) {
            if ('blocks' == $base) {
                foreach ($merge as $block => $source) {
                    $tbs->MergeBlock($block, $source);
                }
            }
            else {
                $tbs->MergeField($base, $merge);
            }
        }

        $ae = $this->container->get('jcs.twig.adminextension');

        // send back the file
        $tbs->Show(OPENTBS_DOWNLOAD, $file_name);
    }

    /**
     * Generate a file from a template, merge the fields, and send the file as a download
     *
     * @param DocTemplate $doc entity
     * @param array $data Array of merge fields
     */
    public function show(DocTemplate $doc, $data)
    {
        if (empty($doc)) {
            return false;
        }

        $this->tbs = $this->container->get('opentbs');
        $ae = $this->container->get('jcs.twig.adminextension');

        $this->tbs->SetOption('noerr', true);

        $this->tbs->LoadTemplate($doc->getFile(), OPENTBS_ALREADY_UTF8); // OPENTBS_DEFAULT, OPENTBS_ALREADY_UTF8, OPENTBS_ALREADY_XML

        // get the field map
        $fields = $this->getMap($data);

        $this->mergeBlocks($fields);

        // file name is Client name + the original template file name
        $file_name = $ae->formatName($data['client']->getFirstname(), $data['client']->getLastname(), $data['client']->getTitle());
        $file_name = $ae->formatFilename($file_name) . '_' . $doc->getOriginalName();

        // send back the file
        $this->tbs->Show(OPENTBS_DOWNLOAD, $file_name);
    }

    private function mergeBlocks($fields)
    {
        // do the field merge
        foreach ($fields as $base => $merge) {
            if ('blocks' == $base) {
                foreach ($merge as $block => $source) {
                    $this->tbs->MergeBlock($block, $source);
                }
            }
            else {
                $this->tbs->MergeField($base, $merge);
            }
        }
    }

    /**
     * Create the template field replace map
     *
     * @param array $data
     * @return array Field Map
     */
    protected function getMap($data)
    {
        $re = [];
        $ae = $this->container->get('jcs.twig.adminextension');

        // data blocks
        if (isset($data['blocks']['problem'])) {
            $re['blocks']['problem'] = [];
            foreach ($data['blocks']['problem'] as $problem) {
                $re['blocks']['problem'][] = $this->getProblemMap($problem);
            }
        }
        if (isset($data['blocks']['client'])) {
            $re['blocks']['client'] = [];
            foreach ($data['blocks']['client'] as $client) {
                $re['blocks']['client'][] = $this->getClientMap($client, true);
            }
        }
        if (isset($data['blocks']['casecount'])) {
            $re['blocks']['casecount'] = $data['blocks']['casecount'];
        }

        // Client
        if (!empty($data['client']) && $data['client'] instanceof Client) {
            //$client = $data['client'];
            $re['uf'] = $this->getClientMap($data['client']);
        }

        // debts
        if (isset($data['debts'])) {
            $re['ha'] = $this->getDebtMap($data['debts']);
        }

        // user fields
        $re['in'] = $this->getUserMap();

        // spec fields
        $re['sp'] = $this->getSpecMap();

        // case history
        if (isset($data['history'])) {
            $re['sp']['esettortenet'] = $data['history'];
        }

        // Catering
        if (!empty($data['catering']) && $data['catering'] instanceof Catering) {
            $re['et'] = $this->getCateringMap($data['catering']);
        }

        // Homehelp
        if (!empty($data['homehelp']) && $data['homehelp'] instanceof HomeHelp) {
            $re['go'] = $this->getHomehelpMap($data['homehelp']);
        }

        // echo '<pre>' , print_r($re), '</pre>';

        return $re;
    }

    private function getProblemMap($problem)
    {
        $ae = $this->container->get('jcs.twig.adminextension');

        $events = [];
        if (count($problem['events'])) {
            foreach ($problem['events'] as $event) {
                $events[] = $this->getEventMap($event);
            }
        }
        else {
            $events[] = [
                'datum' => '',
                'description' => 'Nincsen megjeleníthető esemény'
            ];
        }

        return [
            'title' => $problem['problem']->getTitle(),
            'assigned_to' => ($problem['problem']->getAssignee() ?
                $ae->formatName($problem['problem']->getAssignee()->getFirstname(), $problem['problem']->getAssignee()->getLastname())
                : ''),
            'status' => $ae->problemStatus($problem['problem']),
            'events' => $events
        ];
    }

    private function getEventMap($event) {
        $ae = $this->container->get('jcs.twig.adminextension');

        return [
            'datum' => sprintf('[%s]', $ae->formatDate($event->getEventDate(), 'sd')),
            'description' => $event->getDescription()
        ];
    }

    private function getDebtMap($debts)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        $re = [];
        $sum_managed = 0;
        $sum_registered = 0;
        // sum up the debts
        foreach($debts as $provider) {
            $re[$provider['key'] . 'nyilv'] = $ae->formatCurrency($provider['registered']);
            $re[$provider['key'] . 'kezelt'] = $ae->formatCurrency($provider['managed']);
            $sum_managed += $provider['managed'];
            $sum_registered += $provider['registered'];
        }
        // fill in the procents
        foreach($debts as $provider) {
            $re[$provider['key'] . 'kezeltszaz'] = !empty($provider['registered']) ?
                round($provider['managed'] / $sum_managed * 100) . '%':
                '';
        }
        $re['osszesnyilv'] = $ae->formatCurrency($sum_registered);
        $re['osszeskezelt'] = $ae->formatCurrency($sum_managed);

        return $re;
    }

    /**
     * Return date fields
     * @return array
     */
    private function getSpecMap()
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        return [
            'datum' => $ae->formatDate(new \DateTime()),
            'ev' => date('Y'),
        ];
    }

    /**
     * Return the usre related fields
     * @return array
     */
    private function getUserMap()
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        $user = $this->container->get('security.context')->getToken()->getUser();

        return [
            'nev' => $ae->formatName($user->getFirstname(), $user->getLastname()),
            'email' => $user->getEmail(),
        ];
    }

    /**
     * Return the Client related fields
     * @param \JCSGYK\AdminBundle\Entity\Client $client
     * @return array
     */
    private function getClientMap(Client $client, $with_problems = false)
    {
        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');
        $re = [
            'szam' => $client->getCaseLabel(),
            'nev' => $ae->formatName($client->getFirstname(), $client->getLastname(), $client->getTitle()),
            'titulus' => $client->getTitle(),
            'csaladinev' => $client->getLastname(),
            'utonev' => $client->getFirstname(),
            'nem' => $ae->gender($client->getGender()),
            // birth
            'szuletesihely' => $client->getBirthPlace(),
            'szuletesiido' => $ae->formatDate($client->getBirthDate()),
            'szuletesinev' => $ae->formatName($client->getBirthFirstname(), $client->getBirthLastname(), $client->getBirthTitle()),
            'szuletesititulus' => $client->getBirthTitle(),
            'szuletesicsaladinev' => $client->getBirthLastname(),
            'szuletesiutonev' => $client->getBirthFirstname(),
            // mother
            'anyjaneve' => $ae->formatName($client->getMotherFirstname(), $client->getMotherLastname(), $client->getMotherTitle()),
            'anyjatitulusa' => $client->getMotherTitle(),
            'anyjacsaladineve' => $client->getMotherLastname(),
            'anyjautoneve' => $client->getMotherFirstname(),
            // ids
            'taj' => $client->getSocialSecurityNumber(),
            'szemszam' => $client->getIdentityNumber(),
            'szigszam' => $client->getIdCardNumber(),
            // contact
            'mobil' => $ae->formatPhone($client->getMobile()),
            'telefon' => $ae->formatPhone($client->getPhone()),
            'fax' => $ae->formatPhone($client->getFax()),
            'email' => $client->getEmail(),
            // address
            'lakohely' => $ae->formatAddress($client->getZipCode(), $client->getCity(), $client->getStreet(), $client->getStreetType(), $client->getStreetNumber(), $client->getFlatNumber()),
            'irszam' => $client->getZipCode(),
            'telepules' => $client->getCity(),
            'kozterulet' => $client->getStreet(),
            'kozteruletjellege' => $client->getStreetType(),
            'hazszam' => $client->getStreetNumber(),
            'emeletajto' => $ae->formatSSN($client->getFlatNumber()),
            // location
            'tartozkodasihely' => $ae->formatAddress($client->getLocationZipCode(), $client->getLocationCity(), $client->getLocationStreet(), $client->getLocationStreetType(), $client->getLocationStreetNumber(), $client->getLocationFlatNumber()),
            'tartirszam' => $client->getLocationZipCode(),
            'tarttelepules' => $client->getLocationCity(),
            'tartkozterulet' => $client->getLocationStreet(),
            'tartkozteruletjellege' => $client->getLocationStreetType(),
            'tarthazszam' => $client->getLocationStreetNumber(),
            'tartemeletajto' => $client->getLocationFlatNumber(),
            'allampolgarsag' => $client->getCitizenship(),
            'allampjogallas' => $client->getCitizenshipStatus(),
            // parameters
//                'csaladiosszetetel' => $ae->getParam($client->getParam(103)),
//                'vegzettseg' => $ae->getParam($client->getParam(101)),
//                'gazdaktiv' => $ae->getParam($client->getParam(102)),
//                // other
//                'igenylok' => $client->getParam(104),
            'megjegyzes' => $client->getNote(),
            // megbízott
            'megbizott' => $ae->formatName($client->getGuardianFirstname(), $client->getGuardianLastname()),
            'megbizottcsaladineve' => $client->getGuardianLastname(),
            'megbizottutoneve' => $client->getGuardianFirstname(),
            // is archived?
            'archiv' => $client->getIsArchived() ? 'archivált' : 'aktív',
            // esetgazda
            'esetgazda' => !empty($client->getCaseAdmin()) ? $ae->formatName($client->getCaseAdmin()->getFirstName(), $client->getCaseAdmin()->getLastName()) : '',
        ];

        // utility provider ids
        $ups = $em->getRepository("JCSGYKAdminBundle:Utilityprovider")->findAll();
        foreach ($ups as $up) {
            $re[$up->getTemplateKey() . 'id'] = '';
        }

        $client_provider_ids = $client->getUtilityprovidernumbers();
        foreach ($client_provider_ids as $pid) {
            $re[$pid->getUtilityprovider()->getTemplatekey() . 'id'] = $pid->getValue();
        }

        if ($with_problems) {
            $problems = $client->getProblems();
            $pl = [];
            foreach ($problems as $problem) {
                $param = $problem->getParams();
                $param = $ae->getParam(reset($param));
                $status = $problem->getIsActive() ? '' : 'lezárt';
                $assignee = !empty($problem->getAssignee()) ? $ae->formatName($problem->getAssignee()->getFirstName(), $problem->getAssignee()->getLastName()) : '';

                $pl[] = ['p' => sprintf("%s - %s (%s) %s \n", $problem->getTitle(), $param, $status, $assignee)];
            }
            $re['problems'] = $pl;
        }

        return $re;
    }

    /**
     * Return the catering related fields
     * @return array
     */
    private function getCateringMap(Catering $catering)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        // check the cost for the day
        $table = $this->container->get('jcs.ds')->getOption('cateringcosts');
        $daily_cost = $this->container->get('jcs.invoice')->getCostForADay($catering, $table);

        return [
            'jovedelem' => $ae->formatCurrency($catering->getIncome()),
            'dij'       => $ae->formatCurrency($daily_cost),
            'klub'      => $catering->getClub()->getName(),
            'klubcim'   => $catering->getClub()->getAddress(),
            'klubtel'   => $catering->getClub()->getPhone(),
        ];
    }

    /**
     * Return the homehelp related fields
     * @return array
     */
    private function getHomehelpMap(HomeHelp $homehelp)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        // check the cost for the day
        $table = $this->container->get('jcs.ds')->getOption('homehelpcosts');
        $daily_cost = $this->container->get('jcs.invoice')->getCostForADay($homehelp, $table);

        return [
            'jovedelem' => $ae->formatCurrency($homehelp->getIncome()),
            'dij'       => $ae->formatCurrency($daily_cost),
            'klub'      => $homehelp->getClub()->getName(),
            'klubcim'   => $homehelp->getClub()->getAddress(),
            'klubtel'   => $homehelp->getClub()->getPhone(),
        ];
    }

}