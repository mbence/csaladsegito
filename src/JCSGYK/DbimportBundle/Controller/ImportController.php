<?php

namespace JCSGYK\DbimportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JCSGYK\AdminBundle\Entity\Person;
use JCSGYK\AdminBundle\Entity\Utilityprovider;
use Symfony\Component\HttpFoundation\Request;

class ImportController extends Controller
{
    private $companyID = 1;
    private $utilityproviderList = [];

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
            //'Country' => '',
            'ZipCode' => 'ZipCode',
            'City' => 'City',
            'Street' => 'Street',
            //'StreetType' => '',
            'StreetNumber' => 'StreetNum',
            'FlatNumber' => 'FlatNum',
            //'LocationCountry' => '',
            'LocationZipCode' => 'ZipCode1',
            'LocationCity' => 'City1',
            'LocationStreet' => 'Street1',
            //'LocationStreetType' => '',
            'LocationStreetNumber' => 'StreetNum1',
            'LocationFlatNumber' => 'FlatNum1',
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
            'GuardianFirstname' => 'DelegateName2',
            'GuardianLastname' => 'DelegateName1'
        ];

        $date_fields = ['BirthDate', 'CreatedAt', 'ModifiedAt'];
        $titles = [1 => '', 2 => 'dr.', 3 => 'özv.', 4 => 'id.', 5=> 'ifj.'];
        $phone_fields = ['Mobile', 'Phone', 'Fax'];

        $this->truncate('person');
        $this->truncate('utilityprovider');

        $db = $this->get('doctrine.dbal.csaszir_connection');
        $sql = "SELECT p.*, a1.*, a2.ZipCode as ZipCode1 ,a2.City as City1, a2.Street as Street1, a2.StreetNum as StreetNum1, a2.FlatNum as FlatNum1 FROM Persons p, Addresses a1, Addresses a2 WHERE p.Address_ID=a1.Address_ID AND p.Location_ID=a2.Address_ID AND p.Type=1";
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $persons = $db->fetchAll($sql);

        $em = $this->getDoctrine()->getManager();

        foreach ($persons as $imp) {
            $p = new Person();
            $p->setCompanyId($this->companyID);

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
                // Street type
                elseif ('Street' == $to) {
                   list($val, $st) = $this->streetFix($imp[$from]);
                   $p->setStreetType($st);
                }
                // Street Number
                elseif ('StreetNumber' == $to || 'LocationStreetNumber' == $to) {
                    $val = strtr($imp[$from], ['/' => '', '.' => '']);
                }
                // Location Street type
                elseif ('LocationStreet' == $to) {
                   list($val, $st) = $this->streetFix($imp[$from]);
                   $p->setLocationStreetType($st);
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
                if ($val === 0 || $val === '0'
                        || $val === '00000000000' || $val === '000000000'
                        || $val === '' || $val === 'nincs megadva' || $val === 'uaz'
                        || $val === '?' || $val == 'Anyja neve 1' || $val == 'Anyja neve 2'
                        || (is_string($val) && preg_match('/(yyy|xxx|aaa|bbb)/', $val))
                    ) {
                   $val = null;
                }
                // copy birth names
//                if ($to == 'BirthFirstname' && empty($val)) {
//                    $val = $p->getFirstname();
//                }
//                if ($to == 'BirthLastname' && empty($val)) {
//                    $val = $p->getLastname();
//                }

                // set the value in the entity
                $p->$setter($val);
            }

            $this->setUtilityproviders($p, $imp);
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

    protected function setUtilityproviders(Person &$person, $imp)
    {
        $em = $this->getDoctrine()->getManager();
        $utility_id_fields = [
            'GazmuvekNum',
            'ElmuNum',
            'FotavNum',
            'DijbeszedoNum',
            'JVKNum',
        ];

        foreach ($utility_id_fields as $field) {
            $db_id = $this->convertId('providers', $field);

            $val = ltrim($imp[$field], 0);
            if (!empty($val)) {
                $upid = new Utilityprovider();
                $upid->setValue($val);
                $upid->setPerson($person);
                //$up = $em->getRepository('JCSGYKAdminBundle:Utilityprovider')->find($db_id);
                $upid->setType($db_id);

                $em->persist($upid);
            }
        }
    }

    /**
     * Spilts and validates the street type from the street field
     *
     * @param string $in street and street type
     * @return array
     */
    protected function streetFix($in)
    {
        $street = '';
        $street_type = '';

        // list of all street type names
        $stype_list = [ 'akna', 'alsó', 'alsósor', 'állomás', 'árok', 'átjáró', 'bányatelep', 'bástya', 'bástyája', 'csónakházak', 'domb', 'dűlő', 'dűlőút', 'emlékpark', 'erdészház', 'erdő', 'erdősor', 'fasor', 'fasora', 'felső', 'felsősor', 'forduló', 'főtér', 'gát', 'gyümölcsös', 'határsor', 'határút', 'hegy', 'iskola', 'kapu', 'kert', 'kertek', 'kolónia', 'körönd', 'körtér', 'körút', 'körútja', 'köz', 'középsor', 'kültelek', 'lakópark', 'lejáró', 'lejtő', 'lépcső', 'lépcsősor', 'liget', 'major', 'MÁV pályaudvar', 'menedékház', 'mélyút', 'oldal', 'őrház', 'őrházak', 'park', 'parkja', 'part', 'pályaudvar', 'puszta', 'rakpart', 'rét', 'sétaút', 'sétány', 'sor', 'sportpálya', 'sugárút', 'szőlőhegy', 'tag', 'tanya', 'tanyák', 'telep', 'tere', 'tető', 'tér', 'turistaház', 'udvar', 'utca', 'utcája', 'út', 'útja', 'üdülőpart', 'vadászház', 'vasútállomás', 'vár', 'vízmű', 'víztároló', 'völgy', 'zártkert', 'zug'];
        // short name fix list
        $stype_convert = ['u' => 'utca', 'u.' => 'utca', 'krt' => 'körút', 'krt.' => 'körút'];
        // get and convert the value
        $street = $this->conv($in);
        // split the street type from the street
        $stpos = strrpos($street, ' ');
        if ($stpos !== false) {
            $street_type = trim(substr($street, $stpos));
            // fix some short names
            if (isset($stype_convert[$street_type])) {
                $street_type = $stype_convert[$street_type];
            }
            // check for valid types
            if (in_array($street_type, $stype_list)) {
                // valid street type found
                $street = substr($street, 0, $stpos);
            }
            else {
                $street_type = '';
            }
        }

        return [$street, $street_type];
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


    protected function convertId($group, $id)
    {
        $map = [
            'providers' => [
                'GazmuvekNum' => 3,
                'ElmuNum' => 4,
                'FotavNum' => 5,
                'DijbeszedoNum' => 6,
                'JVKNum' => 7,
            ]
        ];

        return isset($map[$group][$id]) ? $map[$group][$id] : false;
    }
}
