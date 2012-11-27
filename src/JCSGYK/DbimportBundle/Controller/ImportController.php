<?php

namespace JCSGYK\DbimportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JCSGYK\AdminBundle\Entity\Person;
use Symfony\Component\HttpFoundation\Request;

class ImportController extends Controller
{
    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $results = [
            'person' => ''
        ];

        if ($request->isMethod('POST')) {
            
            $table = $request->get('import');
            
            if ('person' == $table || 'all' == $table || 'person100' == $table) {
                $limit = 'person100' == $table ? 100 : 0;
                
                $results['person'] = $this->importPerson($limit);
            }
            $session->set('results', $results);
            
            return $this->redirect($this->generateUrl('jcsgyk_dbimport_homepage'));
        }
        $r = $session->get('results', $results);
        $session->remove('results');
        
        return $this->render('JCSGYKDbimportBundle:Default:index.html.twig', ['results' => $r]);        
    }
    
    /**
     * Import the Persons SQLite table to the projects DB
     * The `person` table gets truncated on every run!
     * 
     * @param int $limit import limit
     * @return int number of imported rows
     */
    protected function importPerson($limit = 100)
    {
        $n = 0;

        $field_map = [
            'Id' => 'Person_ID',
            'Title' => 'Title',
            'Firstname' => 'Name2',
            'Lastname' => 'Name1',
            'Gender' => 'GenderType',
            'BirthDate' => 'BirthDate',
            'BirthPlace' => 'BirthPlace',
            'BirthFirstname' => 'ChildName2',
            'BirthLastname' => 'ChildName1',
            'MotherFirstname' => 'MotherName2',
            'MotherLastname' => 'MotherName1',
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
        
        $date_fields = ['BirthDate', 'CreatedAt', 'ModifiedAt'];
        $titles = [1 => '', 2 => 'dr.', 3 => 'özv.', 4 => 'id.', 5=> 'ifj.'];
        $phone_fields = ['Mobile', 'Phone', 'Fax'];
        
        $this->truncate('person');

        $db = $this->get('doctrine.dbal.csaszir_connection'); 
        $sql = "SELECT * FROM persons WHERE Type=1";
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $persons = $db->fetchAll($sql);

        $em = $this->getDoctrine()->getManager();
        
        foreach ($persons as $imp) {
            $p = new Person();
            foreach ($field_map as $to => $from) {
                $val = null;
                $setter = 'Set' . $to;
                // check and convert date fields
                if (in_array($to, $date_fields)) {
                    $val = $this->getDate($imp[$from]);
                }
                // mobile and phone numbers
                elseif (in_array($to, $phone_fields)) {
                    $val = (int) preg_replace("/[^0-9,.]/", "", $imp[$from]);
                    //$val = $imp[$from];
                    if (strlen($val) == 7) {
                        $val = '1' . $val;
                    }
                }
                // titles
                elseif ('Title' == $to) {
                    $val = $titles[$imp[$from]];
                }
                // everything else
                else {
                    if (!is_array($from)) {
                        $val = $imp[$from];
                    } 
                    // concatenate multi fields
                    else {
                        $val = [];
                        foreach ($from as $f) {
                            $val[] = trim($imp[$f]);
                        }
                        $val = implode(' ', $val);
                    }
                    $val = $this->conv($val);
                }
                // cleanup some unwanted values
                if ($val === 0 || $val === '0' || $val === '00000000000' || $val === '000000000' || $val === '' || $val === 'nincs megadva' || $val === '?') {
                   $val = null;
                }
                // copy birth names
                if ($to == 'BirthFirstname' && empty($val)) {
                    $val = $p->getFirstname();
                }
                if ($to == 'BirthLastname' && empty($val)) {
                    $val = $p->getLastname();
                }
                
                // set the value in the entity
                $p->$setter($val);                
            }
            // manage the object
            $em->persist($p);
            
            // save the entities to the DB
            if ($em->getUnitOfWork()->size() >= 100) {
                $em->flush();
                $em->clear();
            }
            
            $n++;            
        }
        $em->flush();
        
        return $n;
    }
    
    
    /**
     * Convert and trim the Latin-2 fields to UTF-8
     * 
     * @param string $in input
     * @return string converted value
     */
    protected function conv($in)
    {
        return trim(mb_convert_encoding($in, 'UTF-8', 'ISO-8859-2'));
    }
    
    /**
     * Truncate a table in the DB
     * 
     * @param string $table the table name
     */
    protected function truncate($table)
    {
        $db = $this->get('doctrine.dbal.default_connection'); 
        $db->query("TRUNCATE TABLE ". $table);
        unset($db);
    }
    
    /**
     * Convert the Excel 1900 based date format to a DateTime object
     * 
     * @param string $date date string in excel Real format
     * @return DateTime
     */
    protected function getDate($date)
    {
        return new \DateTime(date('r', ($date  * 86400) - ((70 * 365 + 19) * 86400)));
    }
}
