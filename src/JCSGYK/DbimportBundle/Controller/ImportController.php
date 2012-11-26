<?php

namespace JCSGYK\DbimportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JCSGYK\AdminBundle\Entity\Person;

class ImportController extends Controller
{
    public function indexAction($run = false)
    {
        $db = $this->get('doctrine.dbal.csaszir_connection'); 
        $persons = $db->fetchAll("SELECT * FROM persons LIMIT 10");
        
        $field_map = [
            'Id' => 'Person_ID',
            'Name' => ['Name1', 'Name2'],
            'Title' => 'Title',
            'Gender' => 'GenderType',
            'BirthDate' => 'BirthDate',
            'BirthPlace' => 'BirthPlace',
            'BirthName' => ['ChildName1', 'ChildName2'],
            'MotherName' => ['MotherName1', 'MotherName2'],
            'SocialSecurityNumber' => 'TarsAzonJel',
            'IdentityNumber' => 'SzemSzam',
            'IdCardNumber' => 'SzemIgSzam',
            'Mobile' => 'MobileNum',
            'Phone' => 'PhoneNum',
            'Fax' => 'FaxNum',
            'Email' => 'Email',
            'AddressId' => 'Address_ID',
            'LocationId' => 'Location_ID',
            'MartialStatus' => 'MartialStatus',
            'EducationCode' => 'EducationCode',
            'Note' => 'Note',
            'FamilySize' => 'FamilySize',
            'EcActivity' => 'EcActivity',
            'CreatedAt' => 'CreatedOn',
            'CreatedBy' => 'CreatedBy_ID',
            'ModifiedAt' => 'ModifiedOn',
            'ModifiedBy' => 'ModifiedBy_ID',
            'OpenedBy' => 'OpenedBy_ID',
            'DocFile' => 'DocFile',
            'JobType' => 'JobType',
            'DelegateNeeded' => 'DelegateNeeded',
            'DelegateName' => ['DelegateName1', 'DelegateName2']
        ];
        
        $datefields = ['BirthDate', 'CreatedAt', 'ModifiedAt'];
        $titles = [1 => '', 2 => 'dr.', 3 => 'özv.', 4 => 'id.', 5=> 'ifj.'];
        
        $em = $this->getDoctrine()->getManager();
        
        foreach ($persons as $imp) {
            $p = new Person();
            foreach ($field_map as $to => $from) {
                $setter = 'Set' . $to;
                // check and convert date fields
                if (in_array($to, $datefields)) {
                    $val = new \DateTime();
                }
                elseif ('Title' == $to) {
                    $val = $titles[$imp[$from]];
                }
                else {
                    if (!is_array($from)) {
                        $val = $imp[$from];
                    } else {
                        // concatenate multi fields
                        $val = [];
                        foreach ($from as $f) {
                            $val[] = $imp[$f];
                        }
                        $val = implode(' ', $val);
                    }
                    $val = $this->conv($val);
                }
                $p->$setter($val);                
            }
            var_dump($p);
            $em->persist($p);
            $em->flush();
        }
            
        
        
        
        
        //var_dump($persons);
        
        return $this->render('JCSGYKDbimportBundle:Default:index.html.twig');
    }
    
    public function conv($in)
    {
        return mb_convert_encoding($in, 'UTF-8', 'ISO-8859-2');
    }
}
