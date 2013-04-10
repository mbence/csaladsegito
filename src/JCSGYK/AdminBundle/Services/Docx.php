<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JCSGYK\AdminBundle\Entity\Client;

/**
 * Service for Generatinc Docx files from templates
 */
class Docx
{
    /** OpenTBS service */
    private $tbs;
    /** Admin twig extension for formatting */
    private $ae;
    /** Doctrine Entity Manager */
    private $em;


    /** Constructor */
    public function __construct($tbs, $ae, $em)
    {
        $this->tbs = $tbs;
        //$this->tbs->SetOption(['chr_open'=>'{{', 'chr_close'=>'}}']);
        $this->ae = $ae;
        $this->em = $em;
    }

    /**
     * Generate a file from a template, merge the fields, and send the file as a download
     *
     * @param string $template Template filename
     * @param array $data Array of merge fields
     * @param string $file Filename with extension
     */
    public function show($template_id, $data, $file)
    {
        $template = $this->em->getRepository('JCSGYKAdminBundle:Template')->find($template_id);

        if (empty($template)) {
            return false;
        }

        $this->tbs->LoadTemplate($template->getAbsolutePath(), OPENTBS_ALREADY_UTF8); // OPENTBS_DEFAULT, OPENTBS_ALREADY_UTF8, OPENTBS_ALREADY_XML

        // get the field map
        $fields = $this->getMap($data);

        // do the field merge
        foreach ($fields as $base => $merge) {
            $this->tbs->MergeField($base, $merge);
        }

        // send back the file
        $this->tbs->Show(OPENTBS_DOWNLOAD, $file);
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

        // Client
        if (!empty($data['client']) && $data['client'] instanceof Client) {
            $client = $data['client'];
            $re['uf'] = [
                'szam' => $this->ae->formatId($client->getId()),
                'nev' => $this->ae->formatName($client->getFirstname(), $client->getLastname(), $client->getTitle()),
                'titulus' => $client->getTitle(),
                'csaladinev' => $client->getLastname(),
                'utonev' => $client->getFirstname(),
                'nem' => $this->ae->gender($client->getGender()),
                // birth
                'szuletesihely' => $client->getBirthPlace(),
                'szuletesiido' => $this->ae->formatDate($client->getBirthDate()),
                'szuletesinev' => $this->ae->formatName($client->getBirthFirstname(), $client->getBirthLastname(), $client->getBirthTitle()),
                'szuletesititulus' => $client->getBirthTitle(),
                'szuletesicsaladinev' => $client->getBirthLastname(),
                'szuletesiutonev' => $client->getBirthFirstname(),
                // mother
                'anyjaneve' => $this->ae->formatName($client->getMotherFirstname(), $client->getMotherLastname(), $client->getMotherTitle()),
                'anyjatitulusa' => $client->getMotherTitle(),
                'anyjacsaladineve' => $client->getMotherLastname(),
                'anyjautoneve' => $client->getMotherFirstname(),
                // ids
                'taj' => $client->getSocialSecurityNumber(),
                'szemszam' => $client->getIdentityNumber(),
                'szigszam' => $client->getIdCardNumber(),
                // contact
                'mobil' => $this->ae->formatPhone($client->getMobile()),
                'telefon' => $this->ae->formatPhone($client->getPhone()),
                'fax' => $this->ae->formatPhone($client->getFax()),
                'email' => $client->getEmail(),
                // address
                'lakohely' => $this->ae->formatAddress($client->getZipCode(), $client->getCity(), $client->getStreet(), $client->getStreetType(), $client->getStreetNumber(), $client->getFlatNumber()),
                'irszam' => $client->getZipCode(),
                'telepules' => $client->getCity(),
                'kozterulet' => $client->getStreet(),
                'kozteruletjellege' => $client->getStreetType(),
                'hazszam' => $client->getStreetNumber(),
                'emeletajto' => $client->getFlatNumber(),
                // location
                'tartozkodasihely' => $this->ae->formatAddress($client->getLocationZipCode(), $client->getLocationCity(), $client->getLocationStreet(), $client->getLocationStreetType(), $client->getLocationStreetNumber(), $client->getLocationFlatNumber()),
                'tartirszam' => $client->getLocationZipCode(),
                'tarttelepules' => $client->getLocationCity(),
                'tartkozterulet' => $client->getLocationStreet(),
                'tartkozteruletjellege' => $client->getLocationStreetType(),
                'tarthazszam' => $client->getLocationStreetNumber(),
                'tartemeletajto' => $client->getLocationFlatNumber(),
                'allampolgarsag' => $client->getCitizenship(),
                'allampjogallas' => $client->getCitizenshipStatus(),
                // parameters
                'csaladiosszetetel' => $this->ae->getParam($client->getMaritalStatus()),
                'vegzettseg' => $this->ae->getParam($client->getEducationCode()),
                'gazdaktiv' => $this->ae->getParam($client->getEcActivity()),
                // other
                'igenylok' => $client->getFamilySize(),
                'megjegyzes' => $client->getNote(),
                // megbízott
                'megbizott' => $this->ae->formatName($client->getGuardianFirstname(), $client->getGuardianLastname()),
                'megbizottcsaladineve' => $client->getGuardianLastname(),
                'megbizottutoneve' => $client->getGuardianFirstname(),
                'esetgazda' => $this->ae->formatName($client->getCaseAdmin()->getFirstName(), $client->getCaseAdmin()->getLastName()),
            ];
        }

        // spec fields
        $re['sp'] = [
            'datum' => $this->ae->formatDate(),
        ];
        // case history
        if (!empty($data['history'])) {
            $re['sp']['esettortenet'] = $data['history'];
        }

        // echo '<pre>' , print_r($re), '</pre>';

        return $re;
    }
}