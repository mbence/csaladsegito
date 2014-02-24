<?php

namespace JCSGYK\DbimportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JCSGYK\AdminBundle\Entity\User;
use JCSGYK\DbimportBundle\Entity\Client;
use JCSGYK\DbimportBundle\Entity\Utilityprovider;
use JCSGYK\DbimportBundle\Entity\Problem;
use JCSGYK\DbimportBundle\Entity\TmpIdmap;
use JCSGYK\DbimportBundle\Entity\Debt;
use JCSGYK\DbimportBundle\Entity\Event;
use JCSGYK\DbimportBundle\Entity\Archive;
use JCSGYK\DbimportBundle\Entity\UtilityproviderClientnumber;
use JCSGYK\DbimportBundle\Entity\Relation;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

class ImportController extends Controller
{
    private $companyID = 1;
    private $company;

    private $user_id_map = [];
    private $userlist = [];
    private $caselist = [];

    private $reload = null;
    private $session;
    private $xlsxOptions = [
        'regexp' => '#^\d{4}/\d{1,3}$#',
        'c_type' => Client::CW,
        'clean_street_numbers' => true,
    ];

    /**
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */

    public function indexAction()
    {
        $this->memLog('Start');

        $request = $this->getRequest();
        $this->company = $this->get('jcs.ds')->getCompany();

        // disable the profiler, for it fails with the huge query numbers
        if ($this->container->has('profiler'))
        {
            $this->container->get('profiler')->disable();
        }

        $this->session = $request->getSession();
        $results = [];

        $tab = 0;
        if ($request->isMethod('POST')) {

            set_time_limit(0);

            $tab = $request->get('tab');
            $table = $request->get('import');
            if (0 == $tab) { // Jozsefvaros
                $results = $this->jozsefvaros($table);
            }
            elseif (1 == $tab) { // Ferencvaros
                $results = $this->ferencvaros($table);
            }
            elseif (2 == $tab) { // randomizer
                $results = $this->randomizer($table);
            }
        }

        return $this->render('JCSGYKDbimportBundle:Default:index.html.twig', [
            'tab' => $tab,
            'results' => $results,
            'sheets' => $this->getSheets($results),
            'd9sheets' => $this->getSheetsD9($results),
            'reload' => $this->reload
        ]);
    }

    private function jozsefvaros($table)
    {
        $results = [];

        if ('user' == $table || 'all' == $table || 'user10' == $table) {
            $limit = 'user10' == $table ? 10 : 0;

            $results['user'] = $this->importUser($limit, $table);
        }
        if ('client' == $table || 'all' == $table || 'client100' == $table) {
            $limit = 'client100' == $table ? 100 : 0;

            $results['client'] = $this->importClient($limit);
        }
        if ('archive' == $table || 'all' == $table || 'archive100' == $table) {
            $limit = 'archive100' == $table ? 100 : 0;

            $results['archive'] = $this->importArchive($limit);
        }
        if ('problem' == $table || 'all' == $table || 'problem100' == $table) {
            $limit = 'problem100' == $table ? 100 : 0;

            $results['problem'] = $this->importProblem($limit);
        }
        if ('event' == $table || 'all' == $table || 'event100' == $table) {
            $limit = 'event100' == $table ? 100 : 0;

            $results['events'] = $this->importEvent($limit);
        }
        if ('caseadmin' == $table || 'all' == $table || 'caseadmin100' == $table) {
            $limit = 'caseadmin100' == $table ? 100 : 0;

            $results['caseadmin'] = $this->setCaseadmins($limit);
        }

        // Truncate
        if ('truncate' == $table) {
            $results['truncate'] = $this->truncateTables();
        }

        // Gyejo
        $sheets = $this->getSheets();
        if (isset($sheets[$table])) {
            $this->memlog('Start: --- ' . $table . ' ---');
            // import all sheets at once!
            foreach ($sheets as $sheet) {
                $this->memlog('Sheet: ' . $sheet['name']);

                $results['gyejo'] = $this->importXlsx($sheet);
            }
            die();
        }

        return $results;
    }

    // Ferencvaros
    private function ferencvaros($table)
    {
        $results = [];

        // Truncate
        if ('truncate' == $table) {
            $results['truncate'] = $this->truncateTables();
        }
        $d9sheets = $this->getSheetsD9();
        $this->xlsxOptions = [
            'regexp' => '#^.+$#',
            'clean_street_numbers' => false,
        ];

//        var_dump($d9sheets,$table);
        if (isset($d9sheets[$table])) {
            $results['ferenc'] = $this->importXlsx($d9sheets[$table]);
        }

        return $results;
    }

    private function randomizer($table)
    {
        $results = [];

        // randomize names for demo
        if ('random' == $table) {
            $results['random'] = $this->randomizeClients();
        }

        return $results;
    }


    protected function randomizeClients()
    {
        $em = $this->getDoctrine()->getManager();

        // select all clients
        $clients = $em->getRepository('JCSGYKAdminBundle:Client')->findBy(['companyId' => $this->companyID]);

        $fields = [
            'FirstName',
            'LastName',
            'Gender',
            'BirthFirstName',
            'BirthLastName',
            'BirthDate',
            'BirthPlace',
            'MotherFirstName',
            'MotherLastName',
            'SocialSecurityNumber',
            'IdentityNumber',
            'IdCardNumber',
            'Mobile',
            'Phone',
            'Fax',
            'Email',
            'ZipCode',
            'City',
            'Street',
            'StreetNumber',
            'FlatNumber',
            'LocationZipCode',
            'LocationCity',
            'LocationStreet',
            'LocationStreetNumber',
            'LocationFlatNumber',
            'Note',
            'GuardianFirstname',
            'GuardianLastname'
        ];

        $arr = [];
        foreach ($fields as $field) {
            $arr[$field] = [];
        }

        // get the names, addresses, birthdates
        foreach ($clients as $client) {
            foreach ($fields as $field) {
                $getter = 'get' . $field;
                $arr[$field][] = $client->$getter();
            }
        }

        // randomize all data
        foreach ($fields as $field) {
            shuffle($arr[$field]);
        }

        // update the records
        $n = 0;
        foreach ($clients as $client) {
            foreach ($fields as $field) {
                if (isset($arr[$field][$n])) {
                    $setter = 'set' . $field;
                    $client->$setter($arr[$field][$n]);
                }
            }
            $n++;
        }
        // save
        $em->flush();
        // done

        return $n . ' records processed.';
    }

    protected function getSheetsD9($results = [])
    {
        $sheets = [
            'd9sheet1' => [
                'id' => 'd9sheet1',
                'name' => '2013',
                'file' => 'ferencvaros_2013.xlsx',
                'results' => null,
                'note_fields' => [23,24,25,26,27,28],
                'note_text' => 'Hozzátartozók: ',
                'archived' => false,
                'c_type' => Client::FH,
            ],
            'd9sheet2' => [
                'id' => 'd9sheet2',
                'name' => '2012',
                'file' => 'ferencvaros_2012.xlsx',
                'results' => null,
                'note_fields' => [23,24,25,26,27,28],
                'note_text' => 'Hozzátartozók: ',
                'archived' => 2012,
                'c_type' => Client::FH,
            ],
            'd9sheet3' => [
                'id' => 'd9sheet3',
                'name' => '2011',
                'file' => 'ferencvaros_2011.xlsx',
                'results' => null,
                'note_fields' => [23,24,25,26,27,28],
                'note_text' => 'Hozzátartozók: ',
                'archived' => 2011,
                'c_type' => Client::FH,
            ],
            'd9sheet4' => [
                'id' => 'd9sheet4',
                'name' => 'Munka1',
                'file' => 'ferencvaros_pottyos.xlsx',
                'results' => null,
                'note_fields' => [22, 23, 24],
                'note_text' => 'Hozzátartozók: ',
                'archived' => false,
                'c_type' => Client::FH,
            ],
            'd9sheet5' => [
                'id' => 'd9sheet5',
                'name' => 'Gyejo',
                'file' => 'ferencvaros_gyejo.xlsx',
                'results' => null,
                'note_fields' => [],
                'note_text' => '',
                'archived' => false,
                'c_type' => Client::CW,
            ],
        ];
        foreach ($sheets as $n => $sheet) {
            if (isset($result[$sheet['id']])) {
                $sheets[$n]['results'] = $result[$sheet['id']];
            }
        }

        return $sheets;
    }

    protected function getSheets($results = [])
    {
        $sheets = [
            'sheet1' => [
                'id' => 'sheet1',
                'name' => '1000',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet2' => [
                'id' => 'sheet2',
                'name' => '2000',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet3' => [
                'id' => 'sheet3',
                'name' => '2002',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet3b' => [
                'id' => 'sheet3b',
                'name' => '2003',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet4' => [
                'id' => 'sheet4',
                'name' => '2004',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet5' => [
                'id' => 'sheet5',
                'name' => '2006',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet6' => [
                'id' => 'sheet6',
                'name' => '2008',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet7' => [
                'id' => 'sheet7',
                'name' => '2010',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet8' => [
                'id' => 'sheet8',
                'name' => '2011',
                'file' => '2010-2011.xlsx',
                'results' => null
            ],
            'sheet9' => [
                'id' => 'sheet9',
                'name' => '2012',
                'file' => '2012-2014.xlsx',
                'results' => null
            ],
            'sheet10' => [
                'id' => 'sheet10',
                'name' => '2013',
                'file' => '2012-2014.xlsx',
                'results' => null
            ],
            'sheet11' => [
                'id' => 'sheet11',
                'name' => '2014',
                'file' => '2012-2014.xlsx',
                'results' => null
            ],

        ];
        foreach ($sheets as $n => $sheet) {
            if (isset($result[$sheet['id']])) {
                $sheets[$n]['results'] = $result[$sheet['id']];
            }
        }

        return $sheets;
    }

    // clean the tables
    protected function truncateTables()
    {
        $tables = ['client', 'archive', 'relation', 'address'];

        foreach ($tables as $table) {
            $this->truncate($table);
        }

        // clear the user table
        $this->truncate('admin_user');
        $sa = $this->addSuperAdmin();

        $admin = $this->container->get('jcs.twig.adminextension')->formatFilename($sa->getLastName() . ' ' . $sa->getFirstname());
        $this->userlist = [$admin => $sa->getId()];


        // clear the session too
        $this->get('session')->set('caselist', []);

        // reset the client sequence
        $this->get('jcs.seq')->reset($this->company);

        return 'Succesfully truncated ' . implode(', ', $tables);
    }

    protected function memLog($msg) {
        $mem = round(memory_get_usage(true) / 1024 / 1024);
        $this->get('logger')->alert(sprintf('MEM: %s MB, (%s)', $mem, $msg));
    }


    protected function importXlsx($sheet)
    {
        include_once 'PHPExcel/PHPExcel/IOFactory.php';

        $this->memLog('Start xls file load');

        // load the case list
        $this->caselist = $this->session->get('caselist');

        $n = 0;
        $db_dir = '../src/JCSGYK/DbImportBundle/db/';
        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        $objReader->setReadDataOnly(true);
        $objReader->setLoadSheetsOnly($sheet['name']);
        $objPHPExcel = $objReader->load($db_dir . $sheet['file']);

        $names = $objPHPExcel->getSheetNames();

        $request = $this->getRequest();
        $from = $request->request->get('from');
        if (empty($from)) {
            $from = 0;
            // clear the session too
            $this->session->set('caselist', []);
        }

        $length = 10000;

        foreach ($names as $index => $name) {
            $this->memLog('Start xls sheet data read: ' . $name . ', from: ' . $from);

            $sheet_data = $objPHPExcel->getSheet($index)->toArray();
            $sheet_length = count($sheet_data);
            $this->field_map = $this->getXlsxMap($sheet_data[0]);

            $paged_data = array_slice($sheet_data, $from, $length);
            unset($sheet_data);

            $n += $this->processXlsx($paged_data, $this->xlsxOptions, $sheet);
            unset($paged_data);
        }

//        if ($from + $length < $sheet_length) {
//            $this->reload = [
//                'from' => $from + $length,
//                'script' => 'import_' . $sheet['id'],
//            ];
//        }

        // save the case list
        $this->session->set('caselist', $this->caselist);

        $endrow = $from + $length < $sheet_length ? $from + $length : $sheet_length;
        $msg = 'Succesfully imported rows ' . $from . '-' . $endrow;
        $this->memLog($msg);

        return $msg;
    }

    protected function readUsers()
    {
        // find all users
        $users = $this->getDoctrine()->getManager()->getRepository('JCSGYKAdminBundle:User')
                ->findBy(['companyId' => $this->companyID]);

        if (!empty($users)) {
            foreach($users as $user) {
                $full_name = $user->getLastname() . ' ' . $user->getFirstname();
                // normalize user names
                $user_name = strtolower($this->container->get('jcs.twig.adminextension')->formatFilename($full_name));

                // store the users in the userlist
                if (empty($this->userlist[$user_name])) {
                    $this->userlist[$user_name] = $user->getId();
                }
            }
        }

    }

    /**
     * Create new users if not already existing, and return their ID
     * @param string $user_name
     * @return int
     */
    protected function userRemap($full_name)
    {
        $full_name = trim($full_name);
        if (empty($full_name)) {
            return null;
        }

        // initial settings for super users
        if (empty($this->userlist)) {
            // read the existing users
            $this->readUsers();
        }

        // normalize user names
        $user_name = strtolower($this->container->get('jcs.twig.adminextension')->formatFilename($full_name));

        // look for username
        if (isset($this->userlist[$user_name])) {
            return $this->userlist[$user_name];
        }
        else {
            // user not found, create it
            $names = explode(' ', $full_name, 2);
            $firstname = !empty($names[1]) ? $names[1] : '';
            $lastname = !empty($names[0]) ? $names[0] : '';

            if (empty($firstname) && empty($lastname)) {
                return null;
            }

            $userManager = $this->container->get('fos_user.user_manager');
            $em = $this->getDoctrine()->getManager();

            $user = $userManager->createUser();
            $user->setUsername($user_name);
            $user->setPlainPassword('gyejo');
            //$user->setEmail();
            $user->setRoles(['ROLE_CHILD_WELFARE']);
            $user->setEnabled(false);
            $user->setCompanyId($this->companyID);
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $userManager->updateUser($user);

            $em->flush();

            // save userlist
            $this->userlist[$user_name] = $user->getId();
            $user_id = $user->getId();

            $em->detach($user);
            unset($user);

            return $user_id;
        }
    }

    protected function addSuperAdmin()
    {
        // add the superadmin
        $userManager = $this->container->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = new User('8monh30zxj404wggc48gsg840sk088k');
        $user->setUsername('bence');
        $user->setPassword('tknMFBTjss49Q4QeXpslTbKJptBGMGknSRsCfCCsrUqtB8PTk1BviF1J5qehhHwlvCJ5JEtQYAA3qGurfv4ylw==');
        $user->setEmail('mxbence@gmail.com');
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setEnabled(true);
        $user->setCompanyId($this->companyID);
        $user->setFirstname('Bence');
        $user->setLastname('Mészáros');
        $userManager->updateUser($user);

        $em->flush();

        $em->detach($user);

        return $user;
    }

    protected function processXlsx($sheet_data, $options, $sheet)
    {
        $this->memLog('Start processXlsx');

        //var_dump($sheet_data);
        $n = 0;
        $date_fields = ['CreatedAt', 'BirthDate'];

        $em = $this->getDoctrine()->getManager();

        // process the rows
        foreach ($sheet_data as $row_index => $row) {
            //continue;

            // check for a proper case number
            preg_match($options['regexp'], $row[0], $matches);

            if (empty($row[0]) || empty($matches) || $row[0] == 'Név' || $row[0] == 'Sorszám') {
                // skip empty or deleted rows
                if (!empty($row[0]) && $row[0] != 'Név' && $row[0] != 'Sorszám') {
                    var_dump($row[0]);
                }
                continue;
            }

            /*
// ONLY FOR FERENCVÁROS!
            // check for ssn, name and mothers name, and skip if already there
            // get name col
            $rev_map = array_flip($this->field_map);
            $names = explode(' ', $row[$rev_map['Name']], 2);
            if (empty($names[1])) {
                $names[1] = '';
            }
            $mothersnames = explode(' ', $row[$rev_map['MotherName']], 2);
            if (empty($mothersnames[1])) {
                $mothersnames[1] = '';
            }
            $ssn =  Client::cleanupNum($row[$rev_map['SocialSecurityNumber']]);

            $ssn_check_q = $em->createQuery("SELECT c FROM JCSGYKAdminBundle:Client c WHERE c.socialSecurityNumber LIKE :ssn AND c.lastname LIKE :lastname AND c.firstname LIKE :firstname AND c.motherLastname LIKE :mother_lastname AND c.motherFirstname LIKE :mother_firstname")
                ->setParameter('ssn', $ssn)
                ->setParameter('lastname', $names[0])
                ->setParameter('firstname', $names[1])
                ->setParameter('mother_lastname', $mothersnames[0])
                ->setParameter('mother_firstname', $mothersnames[1])
                ->setMaxResults(1);
            $ssn_check = $ssn_check_q->getResult();

            if (!empty($ssn_check)) {
                var_dump($row);
                continue;
            }
            $ssn_check_q->free();
            unset($ssn_check_q);
            unset($ssn_check);
            */

            $a = null;
            $caseAdmin = null;

            $p = new Client();
            $p->setCompanyId($this->companyID);
            $client_type = isset($sheet['c_type']) ? $sheet['c_type'] : (isset($options['c_type']) ? $options['c_type'] : Client::FH);

            $p->setType($client_type);

            foreach ($this->field_map as $index => $to) {
                $from = trim($row[$index]);

                $val = null;
                $setter = 'Set' . $to;
                // check and convert date fields
                if (in_array($to, $date_fields)) {
                    if (!empty($from)) {
                        $from = substr(strtr($from, ['.' => '-']), 0, 10);
                        preg_match('#^\d{4}-\d{2}-\d{2}$#', $from, $dma);
                        if (!empty($dma)) {
                            try {
                               $val = new \DateTime($from);
                            } catch (\Exception $e) {
                                // nothing
                            }
                        }
                        else {
                            $val = $this->getDate($from);
                        }
                    }
                }
                // user remap
                elseif ('CaseAdmin' == $to) {
                    if (!empty($from)) {
                        $val = $this->userRemap($from);
                        $p->setCreatedBy($val);
                    }
                }
                // Street type
                elseif ('Street' == $to) {
                   list($val, $st) = $this->streetFix($from, false);
                   $p->setStreetType($st);
                }
                // Street Number
                elseif ('StreetNumber' == $to || 'LocationStreetNumber' == $to) {
                    if ($options['clean_street_numbers']) {
                        $from = strtr($from, ['/' => '', '.' => '']);
                    }
                    $val = trim($from);
                }
                // Location Street type
                elseif ('LocationStreet' == $to) {
                   list($val, $st) = $this->streetFix($from, false);
                   $p->setLocationStreetType($st);
                }
                // Zip
                elseif ('ZipCode' == $to || 'LocationZipCode' == $to) {
                    $val = substr($from, 0, 4);
                }
                // Name
                elseif ('Name' == $to) {
                    $names = explode(' ', $from, 2);
                    if (empty($from)) {
                        $names = ['ismeretlen', 'ismeretlen'];
                    }
                    if (empty($names[0])) {
                        $names[0] = '-';
                    }
                    if (empty($names[1])) {
                        $names[1] = '-';
                    }
                    $p->setFirstname($names[1]);
                    $p->setLastname($names[0]);

                    continue;
                }
                // Birth Name
                elseif ('BirthName' == $to) {
                    $names = explode(' ', $from, 2);
                    if (empty($from)) {
                        $names = ['', ''];
                    }
                    if (empty($names[0])) {
                        $names[0] = '-';
                    }
                    if (empty($names[1])) {
                        $names[1] = '-';
                    }
                    $p->setBirthFirstname($names[1]);
                    $p->setBirthLastname($names[0]);

                    continue;
                }
                // Mothers name
                elseif ('MotherName' == $to) {
                    $names = explode(' ', $from, 2);
                    if (empty($from)) {
                        $names = ['ismeretlen', 'ismeretlen'];
                    }
                    if (empty($names[0])) {
                        $names[0] = '-';
                    }
                    if (empty($names[1])) {
                        $names[1] = '-';
                    }
                    $p->setMotherFirstname($names[1]);
                    $p->setMotherLastname($names[0]);

                    continue;
                }
                // Guardian Name
                elseif ('Guardian' == $to) {
                    $names = explode(' ', $from, 2);
                    if (empty($from)) {
                        $names = ['', ''];
                    }
                    if (empty($names[0])) {
                        $names[0] = '-';
                    }
                    if (empty($names[1])) {
                        $names[1] = '-';
                    }
                    $p->setGuardianFirstname($names[1]);
                    $p->setGuardianLastname($names[0]);

                    continue;
                }
                // guardian phone hack
                elseif ('GuardianPhone' == $to) {
                    if (!empty($from)) {
                        $p->setGuardianFirstname(sprintf('%s (%s)', $p->getGuardianFirstname(), $from));
                    }
                    continue;
                }
                elseif ('IsArchived' == $to) {

                    // set archived
                    $archived = strpos($from, 'Lezárva') === false ? 0 : 1;

                    // get archived at year
                    if ($archived) {
                        $apices = explode("\n", $from);
                        $archived_year = trim(end($apices));
                        if (!is_numeric($archived_year)) {
                            $archived_year = $p->getCaseYear();
                        }

                        // create the archive record
                        $a = new Archive();
                        //var_dump($arch_date);
                        $a->setCreatedAt(new \DateTime($archived_year . '-12-31'));
                        $a->setType(87);  // Closed - other
                        // we still need the client id
                    }

                    $val = $archived;
                }
                elseif ('CaseLabel' == $to) {
                    if ($p->getType() == Client::CW) {
                        list($cyear, $cnum) = explode('/', $from);
                        $p->setCaseNumber($cnum);
                        $p->setCaseYear($cyear);
                    }
                    $val = $from;
                }
                elseif ('CaseType' == $to) {
                    // only look for 'ÁN'

                    if (strpos($from, 'ÁN') !== false) {
                        $p->setParams([101 => 113]);
                    }

                    continue;
                }
                elseif ('MotherSSN' == $to) {

                    continue;
                }
                // everything else
                else {
                    $val = $from;
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

                // set the value in the entity
                $p->$setter($val);
            }

            // process the notes
            if (!empty($sheet['note_fields'])) {
                $note_tmp = [];
                foreach ($sheet['note_fields'] as $notefield) {
                    $val = trim($row[$notefield]);
                    if (!empty($val)) {
                        $note_tmp[] = $val;
                    }
                }
                $val = '';
                if (!empty($note_tmp)) {
                    if (!empty($sheet['note_text'])) {
                        $val = $sheet['note_text'];
                    }
                    $val .= implode(', ', $note_tmp);

                    $p->setNote($val);
                }
            }

            // check the sheet archived prop
            if (!empty($sheet['archived'])) {
                // create the archive record
                $a = new Archive();
                //var_dump($arch_date);
                $a->setCreatedAt(new \DateTime($sheet['archived'] . '-12-31'));
                $a->setType(87);  // Closed - other
                // we still need the client id
                $p->setIsArchived(1);
            }

            // set the city
            $p->setCity('Budapest');
            $ls = $p->getLocationStreet();
            if (!empty($ls)) {
                $p->setLocationCity('Budapest');
            }

            // check for a valid case number (in some cases, there is no CaseNumber provided)
            if (empty($p->getCaseNumber())) {
                // get a new number and fix the record
                $nextVal = $this->get('jcs.seq')->nextVal($this->company);
                if (false === $nextVal) {
                    // this is really bad
                    throw new HttpException(500);
                }

                $p->setCaseYear($nextVal['year']);
                $p->setCaseNumber($nextVal['id']);
                // set the visible case number
                $p->setCaseLabel($this->container->get('jcs.twig.adminextension')->formatCaseNumber($p));
            }

            // manage the object
            $em->persist($p);
            $em->flush();

            // save the archive
            if (!is_null($a)) {
                $a->setClientId($p->getId());
                $a->setCreatedBy($p->getCaseAdmin());
                $em->persist($a);
                $em->flush();
                $em->detach($a);
                unset($a);
            }

            // Mother data and relation only for Child Welfare
            if ($p->getType() == Client::CW) {
                // check the case list if we already had this mother
                if (!empty($this->caselist[$p->getCaseLabel()])) {
                    // case found, use the mother id
                    $mother_id = $this->caselist[$p->getCaseLabel()];
                }
                else {
                    // save the mother
                    $mother = new Client();
                    $mother->setCompanyId($this->companyID);
                    $mother->setType(Client::PARENT);
                    $mother->setCreatedBy($p->getCreatedBy());
                    $mother->setIsArchived(false);
                    $mother->setFirstname($p->getMotherFirstname());
                    $mother->setLastname($p->getMotherLastname());
                    $mother->setGender(2);
                    // set case admin and numbers
                    $mother->setCaseYear($p->getCaseYear());
                    $mother->setCaseNumber($p->getCaseNumber());
                    $mother->setCaseAdmin($p->getCaseAdmin());
                    // set the visible case number
                    $mother->setCaseLabel($p->getCaseLabel());

                    $em->persist($mother);
                    $em->flush();

                    $mother_id = $mother->getId();
                    $this->caselist[$p->getCaseLabel()] = $mother_id;

                    $em->detach($mother);
                    unset($mother);
                }

                // save the relation
                $rel = new Relation();
                $rel->setType(Relation::MOTHER);
                $rel->setChildId($p->getId());
                $rel->setParentId($mother_id);

                $em->persist($rel);
                $em->flush();

                $em->detach($rel);
                unset($rel);
            }
            $last_p_id = $p->getId();
            $em->flush();
            $em->detach($p);
            unset($p);
            $em->clear();

            $n++;
            if ($n % 100 == 0) {
                $this->memLog('Processed rows: ' . $n);
            }
        }

        if (!empty($last_p_id)) {
            // setup sequence with the last id
            $this->get('jcs.seq')->reset(['id' => $this->companyID, 'sequencePolicy' => 1], $last_p_id);
        }

        return $n;
    }

    protected function getXlsxMap($first_row)
    {
        $map = [];
        // map of the xlsx columns
        // if multiple columns have the same name, we use an array with the target field names
        $fieldmap = [
            'Ügyirat szám' => 'CaseLabel',
            'taj' => ['SocialSecurityNumber', 'MotherSSN'],
            'Taj' => ['SocialSecurityNumber', 'MotherSSN'],
            'Állapot' => 'IsArchived',
            'Aktuális Állapot' => 'IsArchived',
            'akta nyitás dátuma' => 'CreatedAt',
            'Ügy tipusa' => 'CaseType',
            'Gyermek ( kliens ) neve' => 'Name',
            'Születési dátum' => 'BirthDate',
            'Anya taj száma' => 'MotherSSN',
            'Anyja leánykori neve' => 'MotherName',
            'Családgondozó neve' => 'CaseAdmin',
            'Irányitó- szám' => ['ZipCode', 'LocationZipCode'],
            'Utca' => ['Street', 'LocationStreet'],
            'Házszám' => ['StreetNumber','LocationStreetNumber'],
            'Emelet/ajtó' => ['FlatNumber','LocationFlatNumber'],

            'Megjegyzés' => 'Note',

            // ferencvaros
            'Név' => 'Name',
            'Családgondozó' => 'CaseAdmin',
            'Születési név' => 'BirthName',
            'Anyja neve' => 'MotherName',
            'Születési hely' => 'BirthPlace',
            'Születési idő' => 'BirthDate',
            'telefonszám' => ['Phone', 'GuardianPhone'],
            'Lakcím (utca)' => 'Street',
            'Lakcím (hsz.em.a.)' => 'StreetNumber',
            'Tart. hely (utca)' => 'LocationStreet',
            'Tart. Hely (hsz.em.a.)' => 'LocationStreetNumber',
            'TAJ szám' => 'SocialSecurityNumber',
            'állampolgárság' => 'Citizenship',
            'Törvényes képviselő neve' => 'Guardian',
            'Ellátás megkezdésének dátuma' => 'CreatedAt',

            // ferencvaros gyejo
            'Sorszam' => 'CaseNumber',
            'Szül. idő' => 'BirthDate',
            'TAJ' => 'SocialSecurityNumber',
            'Ir. Sz.' => 'ZipCode',
            'Cím' => 'Street',
            'kezdete' => 'CreatedAt',
            'Csg.' => 'CaseAdmin',
            'Anya neve' => 'MotherName',
        ];

        // fields that we already found
        $seen_fields = [];
        $unmapped_fields = [];
        foreach ($first_row as $index => $cell) {
            // clean up cell name
            $cell = trim(strtr($cell, ["\n" => "", '    ' => '', '(Á)' => '']));

            if (!isset($fieldmap[$cell])) {
                $unmapped_fields[] = $cell;
            }
            else {
                $target = $fieldmap[$cell];
                if (is_array($target)) {
                    // check if we saw this column?
                    if (empty($seen_fields[$cell])) {
                        // first occasion
                        $target = $target[0];
                        $seen_fields[$cell] = 1;
                    }
                    elseif(isset($target[$seen_fields[$cell]])) {
                        // not the first time we see this col
                        $target = $target[$seen_fields[$cell]];
                        $seen_fields[$cell]++;
                    }
                    else {
                        continue;
                    }
                }
                $map[$index] = $target;
            }
        }

        //var_dump($map, $unmapped_fields);

        return $map;
    }

    protected function importArchive($limit = 100)
    {

        $n = 0;
        // get archives from old sqlite db
        $db = $this->get('doctrine.dbal.csaszir_connection');
        $sql = "SELECT p.Person_ID, a.* from PersonArchives p, Archives a WHERE p.Archive_ID=a.Archive_ID";
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $events = $db->fetchAll($sql);

        //var_dump($events);
        $id_map = $this->getIdMap();

        $field_map = [
            'ClientId' => 'Person_ID',
            'Description' => 'Reason',
            'Type' => 'Status',
            'CreatedAt' => 'ArchivedOn',
            'CreatedBy' => 'ArchivedBy_ID',
        ];

        $date_fields = ['CreatedAt'];
        $user_fields = ['CreatedBy'];
        $id_fields = ['Type'];

        $this->truncate('archive');

        $em = $this->getDoctrine()->getManager();

        foreach ($events as $imp) {
            $e = new Archive();

            foreach ($field_map as $to => $from) {
                $val = null;
                $setter = 'Set' . $to;
                // check and convert date fields
                if (in_array($to, $date_fields)) {
                    $val = $this->getDate($imp[$from]);
                }
                // user id remap
                elseif (in_array($to, $user_fields)) {
                    $val = isset($id_map[$imp[$from]]) ? $id_map[$imp[$from]] : null;
                }
                // convert ID-s to the parameter system
                elseif(in_array($to, $id_fields)) {
                    $val = $this->convertId('Archive' . $to, $imp[$from]);
                }
                // Description
                elseif ('Description' == $to) {
                    $val = trim(str_replace([
                            'Lezárás oka..., ',
                            'Lezárás oka...',
                            'Megnyitás oka...',
                            'Lezárás oka, ',
                            'Lezárás oka:',
                            'Lezárás oka.',
                            'Lezárás oka',

                        ], '', $this->conv($imp[$from])));
                }
                // everything else
                else {
                    $val = $this->conv($imp[$from]);
                }

                // set the value in the entity
                $e->$setter($val);
            }
            // manage the object
            $em->persist($e);
            unset($e);

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

    protected function importEvent($limit = 100)
    {
        // disable the profiler, because it fails with the huge query numbers
        if ($this->container->has('profiler'))
        {
            $this->container->get('profiler')->disable();
        }

        $n = 0;
        // get events from old sqlite db
        $db = $this->get('doctrine.dbal.csaszir_connection');
        $sql = "SELECT pe.Problem_ID, e.* FROM Events e JOIN ProblemEvents pe ON pe.Event_ID=e.Event_ID";
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $events = $db->fetchAll($sql);

        //var_dump($events);
        $id_map = $this->getIdMap();

        $field_map = [
            'Id' => 'Event_ID',
            'ProblemId' => 'Problem_ID',
            'Description' => 'Desciption',
            'Type' => 'Type',
            'CreatedAt' => 'CreatedOn',
            'CreatedBy' => 'CreatedBy_ID',
            'ModifiedAt' => 'ModifiedOn',
            'ModifiedBy' => 'ModifiedBy_ID',
//            'TitleCode' => 'TitleCode',
//            'ForwardCode' => 'ForwardCode',
//            'ActivityCode' => 'ActivityCode',
            'EventDate' => 'EventDate',
            'ClientVisit' => 'ClientVisit',
            'ClientCancel' => 'ClientCancel',
            'Attachment' => 'DocFile',
        ];

        $date_fields = ['CreatedAt', 'ModifiedAt', 'EventDate'];
        $user_fields = ['CreatedBy', 'ModifiedBy'];
        $id_fields = ['Type'];

        $this->truncate('event');

        $em = $this->getDoctrine()->getManager();

        foreach ($events as $imp) {
            $e = new Event();

            foreach ($field_map as $to => $from) {
                $val = null;
                $setter = 'Set' . $to;
                // check and convert date fields
                if (in_array($to, $date_fields)) {
                    $val = $this->getDate($imp[$from]);
                }
                // user id remap
                elseif (in_array($to, $user_fields)) {
                    $val = isset($id_map[$imp[$from]]) ? $id_map[$imp[$from]] : null;
                }
                // client visit and cancel
                elseif (in_array($to, ['ClientVisit', 'ClientCancel'])) {
                    $val = $imp[$from] == 1 ? 1 : 0;
                }
                // convert ID-s to the parameter system
                elseif(in_array($to, $id_fields)) {
                    $val = $this->convertId('Event' . $to, $imp[$from]);
                }
                // Description
                elseif ('Description' == $to) {
                    // remove event dates from the description if it is the same as in the record
                    $event_dates = [
                        $this->getDate($imp['EventDate'])->format('Y.m.d.'),
                        $this->getDate($imp['EventDate'])->format('Y. m. d.')
                    ];
                    //$val = trim(str_replace($event_dates, '', $this->conv($imp[$from])));
                    $val = trim(preg_replace('/(' . $event_dates[0] . '|' . $event_dates[1] . ')/u', '', $this->conv($imp[$from]), 1));

                    // check for remaining dates that can differ a few days to the records date
                    if (preg_match('/(\d{4})\.(\d{2})\.(\d{2})\.\s*-/', substr($val, 0, 13), $m)) {
                        if (count($m) == 4) {
                            try {
                                $ddate = new \DateTime("{$m[1]}-{$m[2]}-{$m[3]}");
                            }
                            catch (\Exception $e) {
                            }
                            if (!empty($ddate)) {
                                $rec_date = $this->getDate($imp['EventDate']);
                                // we overwrite the records date if there is only a few days (4) difference
                                $days = $ddate->diff($rec_date)->format('%r%a');
                                if (0 < $days && $days < 5) {
                                    $real_date = $ddate;
                                    // ... and remove it from the description
                                    $val = trim(str_replace($m[0], '', $val));
                                }
                            }
                        }
                    }
                    // remove leading dash
                    if (substr($val, 0, 1) == '-') {
                        $val = trim(substr($val, 1));
                    }
                }
                // everything else
                else {
                    $val = $this->conv($imp[$from]);
                }

                // set the value in the entity
                $e->$setter($val);
            }

            // save the parameters
            $this->convertId('Event' . $to, $imp[$from]);
            $param_data = [
                106 => $this->convertId('EventTitleCode', $imp['TitleCode']),
                107 => $this->convertId('EventForwardCode', $imp['ForwardCode']),
                108 => $this->convertId('EventActivityCode', $imp['ActivityCode']),
            ];
            $e->setParams($param_data);

            // overwrite the date field
            if (isset($real_date)) {
                $e->setEventDate($real_date);
                unset($real_date);
            }
            // manage the object
            $em->persist($e);
            unset($e);

            // save the entities to the DB
            if ($em->getUnitOfWork()->size() >= 1000) {
                $em->flush();
                $em->clear();
            }

            $n++;
        }
        $em->flush();

        return $n;
    }

    protected function setCaseadmins($limit = 100)
    {
        $n = 0;
        // get the clients
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('SELECT c FROM JCSGYKAdminBundle:Client c');
        if ($limit) {
            $query->setMaxResults($limit);
        }
        $clients = $query->getResult();

        foreach ($clients as $client) {
            // find the last family help problem
            $problem = $em->createQuery("SELECT p FROM JCSGYKAdminBundle:Problem p WHERE p.client=:client AND p.title LIKE '%CSG%' AND p.assignee IS NOT NULL ORDER BY p.createdAt DESC")
                ->setParameter('client', $client->getId())
                ->setMaxResults(1)
                ->getResult();
            if (empty($problem)) {
                // no CSG problem, lets use the last one
                $problem = $em->createQuery("SELECT p FROM JCSGYKAdminBundle:Problem p WHERE p.client=:client AND p.assignee IS NOT NULL ORDER BY p.createdAt DESC")
                    ->setParameter('client', $client->getId())
                    ->setMaxResults(1)
                    ->getResult();
            }

            $assigned_user = null;
            if (!empty($problem)) {
                $assigned_user = $problem[0]->getAssignee();
                $n++;
            }

            $client->setCaseadmin($assigned_user);
            $em->persist($client);

            // save the entities to the DB
//            if ($n >= 90) {
//                $em->flush();
//                $em->clear();
//            }
        }
        $em->flush();

        return $n;
    }

    protected function importProblem($limit = 100)
    {
        $n = 0;
        // get problems from old sqlite db
        $db = $this->get('doctrine.dbal.csaszir_connection');

        $sql = "SELECT Person_ID, p.* FROM Problems p JOIN PersonProblems ON RootProblem_ID=Problem_ID";
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $problems = $db->fetchAll($sql);

        //var_dump($problems);
        $id_map = $this->getIdMap();

        $field_map = [
            'Id' => 'Problem_ID',
            'ClientId' => 'Person_ID',
            'Title' => 'Title',
            'Description' => 'Desciption',
            'Params' => 'UserProb',
            'IsActive' => 'Status',
            'CreatedAt' => 'CreatedOn',
            'CreatedBy' => 'CreatedBy_ID',
            'ModifiedAt' => 'ModifiedOn',
            'ModifiedBy' => 'ModifiedBy_ID',
            'AssignedTo' => 'AssignedTo_ID',
            'ClosedBy' => 'ClosedBy_ID',
            'ClosedAt' => 'ClosedOn',
            'ConfirmedBy' => 'ClosedBy_ID',
            'ConfirmedAt' => 'ClosedOn',
            'CloseCode' => 'CloseCode',
            'OpenedBy' => 'OpenedBy_ID',
            'Attachment' => 'DocFile',
        ];

        $date_fields = ['CreatedAt', 'ModifiedAt', 'ClosedAt', 'ConfirmedAt'];
        $user_fields = ['CreatedBy', 'ModifiedBy', 'OpenedBy', 'AssignedTo', 'ClosedBy', 'ConfirmedBy'];

        $this->truncate('problem');
        $this->truncate('debt');

        $em = $this->getDoctrine()->getManager();

        foreach ($problems as $imp) {
            $p = new Problem();

            foreach ($field_map as $to => $from) {
                $val = null;
                $setter = 'Set' . $to;
                // check and convert date fields
                if (in_array($to, $date_fields)) {
                    $val = $this->getDate($imp[$from]);
                }
                // user id remap
                elseif (in_array($to, $user_fields)) {
                    $val = isset($id_map[$imp[$from]]) ? $id_map[$imp[$from]] : null;
                }
                elseif ('Params' == $to) {
                    $val =  [105 => $this->convertId('ProblemType', $imp[$from])];
                }
                elseif ('IsActive' == $to) {
                    $val = $imp[$from] == 1 ? 1 : 0;
                }
                // description
                elseif ('Description' == $to) {
                    $val = trim(str_replace('Ügyfél által hozott probléma részletes leírása...', '', $this->conv($imp[$from])));
                }
                // everything else
                else {
                    $val = $this->conv($imp[$from]);
                }


                // set the value in the entity
                $p->$setter($val);
            }
            // manage the object
            $em->persist($p);

            // save the utility provider debts
            $this->saveDebts($imp);

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

    protected function saveDebts($problem)
    {
        $em = $this->getDoctrine()->getManager();
        $utility_id_fields = [
            'dhJVK' => 4,
            'dhElmu' => 1,
            'dhFoGaz' => 2,
            'dhFotav' => 3,
            'dhDijbeszedo' => 6,
            'dhKozos' => 5,
        ];

        foreach ($utility_id_fields as $field => $type) {
            $reg_field = $field . '_1';
            $man_field = $field . '_2';

            if (!empty($problem[$reg_field]) || !empty($problem[$man_field])) {
                $debt = new Debt();
                $debt->setProblemId($problem['Problem_ID']);
                $debt->setRegisteredDebt(trim($problem[$reg_field]));
                $debt->setManagedDebt(trim($problem[$man_field]));
                $debt->setUtilityproviderId($type);

                $em->persist($debt);
            }
        }
    }

    /**
     * Import the users from the old DB
     * @param int $limit
     * @param string $table all || user
     * @return int number of imported rows
     */
    protected function importUser($limit = 10, $table)
    {
        $n = 0;
        // get users from old sqlite db
        $db = $this->get('doctrine.dbal.csaszir_connection');
        $sql = "SELECT p.Name1, p.Name2, p.Email, u.* FROM Persons p, Users u WHERE Person_ID = PJFIG";
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $csaszir_users = $db->fetchAll($sql);

        $userManager = $this->container->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $this->truncate('admin_user');
        $this->truncate('_tmp_idmap');

        // add the superadmin
        $this->addSuperAdmin();
//
//        // add the testers
//        $user = new User('mbxrw64tn4gc88oo8koogwk80wogk84');
//        $user->setUsername('jancsor');
//        $user->setPassword('UlRgqRKTRfNE8twyzle8kclhvqc0hbbSj5nAldhdqHXCczfr8Jn3wMMWfGYcpqqA+OlZz+83lO+bC6wuMz6N4w==');
//        $user->setEmail('jancso@gmail.com');
//        $user->setRoles(['ROLE_ADMIN']);
//        $user->setEnabled(true);
//        $user->setCompanyId($this->companyID);
//        $user->setFirstname('Richárd');
//        $user->setLastname('Jancsó');
//        $userManager->updateUser($user);

        foreach ($csaszir_users as $csaszir_user) {
            $user = $userManager->createUser();
            $user->setUsername(trim($this->decode($csaszir_user['IKFL'])));
            $user->setPlainPassword(trim($this->decode($csaszir_user['VHRD'])));
            $user->setEmail(trim($csaszir_user['Email']));
            $user->setRoles($this->getRoles($csaszir_user['DFGF']));
            $user->setEnabled(true);
            $user->setCompanyId($this->companyID);
            $user->setFirstname($this->conv($csaszir_user['Name2']));
            $user->setLastname($this->conv($csaszir_user['Name1']));
            $userManager->updateUser($user);

            // save id map
            $idmap = new TmpIdmap();
            $idmap->setUserId($user->getId());
            $idmap->setOldId($csaszir_user['PJFIG']);
            $em->persist($idmap);

            $n++;
        }

        $em->flush();
        $em->clear();

        return $n;
    }

    /**
     * Import the Clients SQLite table to the projects DB
     * The `client` table gets truncated on every run!
     *
     * @param int $limit import limit
     * @return int number of imported rows
     */
    protected function importClient($limit = 100)
    {
        $n = 0;

        $id_map = $this->getIdMap();

        $field_map = [
            'Id' => 'Person_ID',
            'CaseNumber' => 'Person_ID',
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
            'Note' => 'Note',
//            'FamilySize' => 'FamilySize',
//            'EcActivity' => 'EcActivity',
//            'MaritalStatus' => 'MartialStatus',
//            'EducationCode' => 'EducationCode',
            'CreatedAt' => 'CreatedOn',
            'CreatedBy' => 'CreatedBy_ID',
            'ModifiedAt' => 'ModifiedOn',
            'ModifiedBy' => 'ModifiedBy_ID',
            'OpenedBy' => 'OpenedBy_ID',
            'DocFile' => 'DocFile',
//            'JobType' => 'JobType',
            'GuardianFirstname' => 'DelegateName2',
            'GuardianLastname' => 'DelegateName1'
        ];

        $date_fields = ['BirthDate', 'CreatedAt', 'ModifiedAt'];
        $user_fields = ['CreatedBy', 'ModifiedBy', 'OpenedBy'];
        $titles = [1 => '', 2 => 'dr.', 3 => 'özv.', 4 => 'id.', 5=> 'ifj.'];
        $phone_fields = ['Mobile', 'Phone', 'Fax'];

        $this->truncate('client');
        $this->truncate('utilityprovider_clientnumber');

        $db = $this->get('doctrine.dbal.csaszir_connection');
        $sql = "SELECT p.*, a1.*, a2.ZipCode as ZipCode1 ,a2.City as City1, a2.Street as Street1, a2.StreetNum as StreetNum1, a2.FlatNum as FlatNum1 FROM Persons p, Addresses a1, Addresses a2 WHERE p.Address_ID=a1.Address_ID AND p.Location_ID=a2.Address_ID AND p.Type=1";
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $clients = $db->fetchAll($sql);

        $em = $this->getDoctrine()->getManager();

        foreach ($clients as $imp) {
            $p = new Client();
            $p->setCompanyId($this->companyID);
            $p->setType(Client::FH);

            foreach ($field_map as $to => $from) {
                $val = null;
                $setter = 'Set' . $to;
                // check and convert date fields
                if (in_array($to, $date_fields)) {
                    $val = $this->getDate($imp[$from]);
                }
                // user id remap
                elseif (in_array($to, $user_fields)) {
                    $val = isset($id_map[$imp[$from]]) ? $id_map[$imp[$from]] : null;
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
                    $val = trim(strtr($this->conv($imp[$from]), ['/' => '', '.' => '']));
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
            // set archived
            $p->setIsArchived($this->isArchived($imp['Person_ID']));
            // set the visible case number
            $p->setCaseLabel($this->container->get('jcs.twig.adminextension')->formatCaseNumber([
                'type' => $p->getType(),
                'case_number' => $p->getCaseNumber(),
                'case_year' => $p->getCaseYear(),
            ]));

            // save the parameters
            $param_data = [
                101 => $this->convertId('EducationCode', $imp['EducationCode']),
                102 => $this->convertId('EcActivity', $imp['EcActivity']),
                103 => $this->convertId('MaritalStatus', $imp['MartialStatus']),
                104 => $imp['FamilySize']
            ];
            $p->setParams($param_data);

            // set the client id to the case number
            $p->setId($p->getCaseNumber());

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

        // setup sequence with the last id
        $this->get('jcs.seq')->reset(['id' => $this->companyID, 'sequencePolicy' => 0], $p->getId());

        return $n;
    }

    protected function isArchived($client_id)
    {
        $db = $this->get('doctrine.dbal.csaszir_connection');
        $sql = "SELECT Status FROM PersonArchives pa, Archives a WHERE pa.Archive_ID=a.Archive_ID AND pa.Person_ID={$client_id} ORDER BY ArchivedOn_T DESC LIMIT 1";

        $archived = $db->fetchAll($sql);

        return !empty($archived[0]['Status']) && $archived[0]['Status'] > 1;
    }

    protected function getIdMap()
    {
        $map = [];
        $res = $this->getDoctrine()->getManager()
            ->createQuery('SELECT p FROM JCSGYKDbimportBundle:TmpIdmap p')
            ->getArrayResult();

        foreach($res as $r) {
            $map[$r['oldId']] = $r['userId'];
        }

        return $map;
    }

    protected function setUtilityproviders(Client &$client, $imp)
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
                $upid = new UtilityproviderClientnumber();
                $upid->setValue($val);
                $upid->setClient($client);
                $upid->setUtilityproviderId($db_id);

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
    protected function streetFix($in, $conv = true)
    {
        $street_type = '';

        // list of all street type names
        $stype_list = [ 'akna', 'alsó', 'alsósor', 'állomás', 'árok', 'átjáró', 'bányatelep', 'bástya', 'bástyája', 'csónakházak', 'domb', 'dűlő', 'dűlőút', 'emlékpark', 'erdészház', 'erdő', 'erdősor', 'fasor', 'fasora', 'felső', 'felsősor', 'forduló', 'főtér', 'gát', 'gyümölcsös', 'határsor', 'határút', 'hegy', 'iskola', 'kapu', 'kert', 'kertek', 'kolónia', 'körönd', 'körtér', 'körút', 'körútja', 'köz', 'középsor', 'kültelek', 'lakópark', 'lejáró', 'lejtő', 'lépcső', 'lépcsősor', 'liget', 'major', 'MÁV pályaudvar', 'menedékház', 'mélyút', 'oldal', 'őrház', 'őrházak', 'park', 'parkja', 'part', 'pályaudvar', 'puszta', 'rakpart', 'rét', 'sétaút', 'sétány', 'sor', 'sportpálya', 'sugárút', 'szőlőhegy', 'tag', 'tanya', 'tanyák', 'telep', 'tere', 'tető', 'tér', 'turistaház', 'udvar', 'utca', 'utcája', 'út', 'útja', 'üdülőpart', 'vadászház', 'vasútállomás', 'vár', 'vízmű', 'víztároló', 'völgy', 'zártkert', 'zug'];
        // short name fix list
        $stype_convert = ['u' => 'utca', 'u.' => 'utca', 'krt' => 'körút', 'krt.' => 'körút'];
        // get and convert the value
        if ($conv) {
            $street = $this->conv($in);
        }
        else {
            $street = trim($in);
        }
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
                'ElmuNum' => 1,
                'GazmuvekNum' => 2,
                'FotavNum' => 3,
                'JVKNum' => 4,
                'DijbeszedoNum' => 6,
            ],
            'EducationCode' => [
                2 => 9,
                3 => 9,
                4 => 10,
                5 => 10,
                6 => 12,
                7 => 13,
                8 => 14,
                9 => 15
            ],
            'EcActivity' => [
                2 => 16,
                3 => 16,
                4 => 16,
                5 => 19,
                6 => 19,
                7 => 23,
                8 => 19,
                9 => 23,
                10 => 24,
                11 => 24,
                12 => 24,
                13 => 24,
                14 => 28,
                15 => 29,
                16 => 30,
            ],
            'MaritalStatus' => [
                2 => 31,
                3 => 32,
                4 => 33,
                5 => 34,
                6 => 35
            ],
            'ProblemType' => [
                2 => 36,
                3 => 37,
                4 => 38,
                5 => 39,
                6 => 40,
                7 => 41,
                8 => 42,
                9 => 43,
                10 => 44,
                11 => 45,
                12 => 46,
                13 => 47,
                14 => 48
            ],
            'EventType' => [
                2 => 50,
                3 => 51,
                4 => 52,
                5 => 53
            ],
            'EventTitleCode' => [
                2 => 54,
                3 => 54,
                4 => 54,
                5 => 54,
                6 => 54,
                7 => 54,
                8 => 60,
                9 => 60,
                10 => 60,
                11 => 63,
                12 => 63
            ],
            'EventForwardCode' => [
                2 => 65,
                3 => 66,
                4 => 67,
                5 => 68,
                6 => 69,
                7 => 70,
                8 => 71,
                9 => 72,
                10 => 73,
                11 => 74
            ],
            'EventActivityCode' => [
                2 => 75,
                3 => 76,
                4 => 77,
                5 => 78,
                6 => 79,
                7 => 80
            ],
            'ArchiveType' => [
                100 => 81,
                104 => 82,
                108 => 83,
                112 => 84,
                116 => 85,
                120 => 86,
                124 => 87,
                128 => 88,
                132 => 89,
                136 => 90,
                1 => 91
            ]
        ];

        return isset($map[$group][$id]) ? $map[$group][$id] : 0;
    }

    protected function decode($in)
    {
        $out = '';
        foreach (str_split($in) as $c) {
            $n = ord($c);
            $n += $n % 2 ? -1 : 1;
            $out .= chr($n);
        }

        return $out;
    }

    protected function getRoles($in)
    {
        $roles = [];
        if ($in == 22150) {
            $roles[] = 'ROLE_ADMIN';
        } elseif ($in == 5200) {
            $roles[] = 'ROLE_FAMILY_HELP';
        } elseif ($in == 1700) {
            $roles[] = 'ROLE_ASSISTANCE';
        }

        return $roles;
    }
}
