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
        $tbs->LoadTemplate($template->getAbsolutePath(), OPENTBS_ALREADY_UTF8); // OPENTBS_DEFAULT, OPENTBS_ALREADY_UTF8, OPENTBS_ALREADY_XML

        // get the field map
        $fields = $this->getMap($data);

        // do the field merge
        foreach ($fields as $base => $merge) {
            $tbs->MergeField($base, $merge);
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
                'esetgazda' => $ae->formatName($client->getCaseAdmin()->getFirstName(), $client->getCaseAdmin()->getLastName()),
            ];
        }

        // utility provider ids
        $providers = $this->container->get('jcs.ds')->getGroup(2);
        $client_provider_ids = $client->getUtilityproviders();
        $prids = [];
        foreach ($client_provider_ids as $pid) {
            $prids[$pid->getType()] = $pid->getValue();
        }

        foreach ($providers as $pr_id => $pr_name) {
            $provider_normalised_name = strtolower($ae->formatFilename($pr_name) . 'id');
            $re['uf'][$provider_normalised_name] = !empty($prids[$pr_id]) ? $prids[$pr_id] : '';
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
        if (!empty($data['history'])) {
            $re['sp']['esettortenet'] = $data['history'];
        }

        // echo '<pre>' , print_r($re), '</pre>';

        return $re;
    }
}