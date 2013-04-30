<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Client;

/**
 * Service for Generatinc Docx files from templates
 */
class Docx
{
    /** Service container */
    private $container;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Generate a file from a template, merge the fields, and send the file as a download
     *
     * @param string $template Template filename
     * @param array $data Array of merge fields
     */
    public function show($template_id, $data)
    {
        $em = $this->container->get('doctrine')->getManager();
        $template = $em->getRepository('JCSGYKAdminBundle:Template')->find($template_id);

        if (empty($template)) {
            return false;
        }

        $tbs = $this->container->get('opentbs');
        //$tbs->SetOption('noerr', true);

        $tbs->LoadTemplate($template->getAbsolutePath(), OPENTBS_ALREADY_UTF8); // OPENTBS_DEFAULT, OPENTBS_ALREADY_UTF8, OPENTBS_ALREADY_XML

        // get the field map
        $fields = $this->getMap($data);
//        var_dump($fields['blocks']['problem'][0]);
//        var_dump($fields['blocks']['problem'][0]['events']);

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

        // file name is Client name + the original template file name
        $file_name = $ae->formatName($data['client']->getFirstname(), $data['client']->getLastname(), $data['client']->getTitle());
        $file_name = $ae->formatFilename($file_name) . '_' . $template->getOriginalName();

        // send back the file
        $tbs->Show(OPENTBS_DOWNLOAD, $file_name);
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
        $em = $this->container->get('doctrine')->getManager();

        if (!empty($data['blocks']['problem'])) {
            $re['blocks']['problem'] = [];

            foreach ($data['blocks']['problem'] as $pr) {
                $events = [];
                if (count($pr['events'])) {
                    foreach ($pr['events'] as $ev) {
                        $events[] = [
                            'datum' => sprintf('[%s]', $ae->formatDate($ev->getEventDate(), 'sd')),
                            'description' => $ev->getDescription()
                        ];
                    }
                }
                else {
                    $events[] = [
                        'datum' => '',
                        'description' => 'Nincsen megjeleníthető esemény'
                    ];
                }

                $re['blocks']['problem'][] = [
                    'title' => $pr['problem']->getTitle(),
                    'assigned_to' => ($pr['problem']->getAssignee() ?
                        $ae->formatName($pr['problem']->getAssignee()->getFirstname(), $pr['problem']->getAssignee()->getLastname())
                        : ''),
                    'status' =>  $ae->problemStatus($pr['problem']),
                    'events' => $events
                ];
            }
        }
        // Client
        if (!empty($data['client']) && $data['client'] instanceof Client) {
            $client = $data['client'];
            $re['uf'] = [
                'szam' => $ae->formatId($client->getId()),
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
                'emeletajto' => $client->getFlatNumber(),
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
                'csaladiosszetetel' => $ae->getParam($client->getMaritalStatus()),
                'vegzettseg' => $ae->getParam($client->getEducationCode()),
                'gazdaktiv' => $ae->getParam($client->getEcActivity()),
                // other
                'igenylok' => $client->getFamilySize(),
                'megjegyzes' => $client->getNote(),
                // megbízott
                'megbizott' => $ae->formatName($client->getGuardianFirstname(), $client->getGuardianLastname()),
                'megbizottcsaladineve' => $client->getGuardianLastname(),
                'megbizottutoneve' => $client->getGuardianFirstname(),
            ];
            $ca = $client->getCaseAdmin();
            if (!empty($ca)) {
                $re['uf']['esetgazda'] = $ae->formatName($client->getCaseAdmin()->getFirstName(), $client->getCaseAdmin()->getLastName());
            }
        }

        // utility provider ids
        $ups = $em->getRepository("JCSGYKAdminBundle:Utilityprovider")->findAll();
        foreach ($ups as $up) {
            $re['uf'][$up->getTemplateKey() . 'id'] = '';
        }
        $client_provider_ids = $client->getUtilityprovidernumbers();
        foreach ($client_provider_ids as $pid) {
            $re['uf'][$pid->getUtilityprovider()->getTemplatekey() . 'id'] = $pid->getValue();
        }

        // debts
        if (isset($data['debts'])) {
            $sum_managed = 0;
            $sum_registered = 0;
            // sum up the debts
            foreach($data['debts'] as $provider) {
                $re['ha'][$provider['key'] . 'nyilv'] = $ae->formatCurrency($provider['registered']);
                $re['ha'][$provider['key'] . 'kezelt'] = $ae->formatCurrency($provider['managed']);
                $sum_managed += $provider['managed'];
                $sum_registered += $provider['registered'];
            }
            // fill in the procents
            foreach($data['debts'] as $provider) {
                $re['ha'][$provider['key'] . 'kezeltszaz'] = !empty($provider['registered']) ?
                    round($provider['managed'] / $sum_managed * 100) . '%':
                    '';
            }
            $re['ha']['osszesnyilv'] = $ae->formatCurrency($sum_registered);
            $re['ha']['osszeskezelt'] = $ae->formatCurrency($sum_managed);
        }

        // user fields
        $user = $this->container->get('security.context')->getToken()->getUser();

        $re['in'] = [
            'nev' => $ae->formatName($user->getFirstname(), $user->getLastname()),
            'email' => $user->getEmail(),
        ];

        // spec fields
        $re['sp'] = [
            'datum' => $ae->formatDate(),
            'ev' => date('Y'),
        ];
        // case history
        if (isset($data['history'])) {
            $re['sp']['esettortenet'] = $data['history'];
        }

        // echo '<pre>' , print_r($re), '</pre>';

        return $re;
    }
}