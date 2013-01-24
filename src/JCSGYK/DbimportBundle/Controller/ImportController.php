<?php

namespace JCSGYK\DbimportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JCSGYK\AdminBundle\Entity\User;
use JCSGYK\DbimportBundle\Entity\Client;
use JCSGYK\DbimportBundle\Entity\Utilityprovider;
use JCSGYK\DbimportBundle\Entity\Problem;
use JCSGYK\DbimportBundle\Entity\TmpIdmap;
use JCSGYK\DbimportBundle\Entity\Debt;

use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;

class ImportController extends Controller
{
    private $companyID = 1;

    private $user_id_map = [];

    /**
     * @Secure(roles="ROLE_SUPERADMIN")
     */

    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $results = [];

        if ($request->isMethod('POST')) {

            $table = $request->get('import');

            if ('user' == $table || 'all' == $table || 'user10' == $table) {
                $limit = 'user10' == $table ? 10 : 0;

                $results['user'] = $this->importUser($limit, $table);
            }
            if ('client' == $table || 'all' == $table || 'client100' == $table) {
                $limit = 'client100' == $table ? 100 : 0;

                $results['client'] = $this->importClient($limit);
            }
            if ('problem' == $table || 'all' == $table || 'problem100' == $table) {
                $limit = 'problem100' == $table ? 300 : 0;

                $results['problem'] = $this->importProblem($limit);
            }
            if ('caseadmin' == $table || 'all' == $table || 'caseadmin100' == $table) {
                $limit = 'caseadmin100' == $table ? 100 : 0;

                $results['caseadmin'] = $this->setCaseadmins($limit);
            }

            $session->set('results', $results);

            //return $this->redirect($this->generateUrl('jcsgyk_dbimport_homepage'));
        }
        $r = $session->get('results', $results);
        $session->remove('results');

        return $this->render('JCSGYKDbimportBundle:Default:index.html.twig', ['results' => $r]);
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
            $problem = $em->createQuery("SELECT p FROM JCSGYKAdminBundle:Problem p WHERE p.client=:client AND p.title LIKE '%CSG%' ORDER BY p.createdAt DESC")
                ->setParameter('client', $client->getId())
                ->setMaxResults(1)
                ->getResult();
            if (empty($problem)) {
                // no CSG problem, lets use the last one
                $problem = $em->createQuery("SELECT p FROM JCSGYKAdminBundle:Problem p WHERE p.client=:client ORDER BY p.createdAt DESC")
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
            'Type' => 'UserProb',
            'Level' => 'ProblemLevel',
            'IsActive' => 'Status',
            'CreatedAt' => 'CreatedOn',
            'CreatedBy' => 'CreatedBy_ID',
            'ModifiedAt' => 'ModifiedOn',
            'ModifiedBy' => 'ModifiedBy_ID',
            'AssignedTo' => 'AssignedTo_ID',
            'ClosedBy' => 'ClosedBy_ID',
            'ClosedAt' => 'ClosedOn',
            'CloseCode' => 'CloseCode',
            'OpenedBy' => 'OpenedBy_ID',
            'Attachment' => 'DocFile',
        ];

        $date_fields = ['CreatedAt', 'ModifiedAt', 'ClosedAt'];
        $user_fields = ['CreatedBy', 'ModifiedBy', 'OpenedBy', 'AssignedTo', 'ClosedBy'];

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
                elseif ('Type' == $to) {
                    $val =  $this->convertId('ProblemType', $imp[$from]);
                }
                elseif ('IsActive' == $to) {
                    $val = $imp[$from] == 1 ? 1 : 0;
                }
                // description
                elseif ('Description' == $to) {
                    $val = str_replace('Ügyfél által hozott probléma részletes leírása...', '', $this->conv($imp[$from]));
                }
                // everything else
                else {
                    $val = $this->conv($imp[$from]);
                }


                // set the value in the entity
                $p->$setter($val);
            }
            // TODO: save the utility provider data

            // manage the object
            $em->persist($p);

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
            'dhJVK' => 7,
            'dhElmu' => 4,
            'dhFoGaz' => 3,
            'dhFotav' => 5,
            'dhDijbeszedo' => 6,
            'dhKozos' => 49,
        ];

        foreach ($utility_id_fields as $field => $type) {
            $reg_field = $field . '_1';
            $man_field = $field . '_2';

            if (!empty($problem[$reg_field]) || !empty($problem[$man_field])) {
                $debt = new Debt();
                $debt->setProblemId($problem['Problem_ID']);
                $debt->setRegisteredDebt(trim($problem[$reg_field]));
                $debt->setManagedDebt(trim($problem[$man_field]));
                $debt->setType($type);
                $debt->setIsActive(true);

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
        $user = new User('8monh30zxj404wggc48gsg840sk088k');
        $user->setUsername('bence');
        $user->setPassword('tknMFBTjss49Q4QeXpslTbKJptBGMGknSRsCfCCsrUqtB8PTk1BviF1J5qehhHwlvCJ5JEtQYAA3qGurfv4ylw==');
        $user->setEmail('mxbence@gmail.com');
        $user->setRoles(['ROLE_SUPERADMIN']);
        $user->setEnabled(true);
        $user->setCompanyId($this->companyID);
        $user->setFirstname('Bence');
        $user->setLastname('Mészáros');
        $userManager->updateUser($user);

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
            'FamilySize' => 'FamilySize',
            'EcActivity' => 'EcActivity',
            'MaritalStatus' => 'MartialStatus',
            'EducationCode' => 'EducationCode',
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
        $user_fields = ['CreatedBy', 'ModifiedBy', 'OpenedBy'];
        $titles = [1 => '', 2 => 'dr.', 3 => 'özv.', 4 => 'id.', 5=> 'ifj.'];
        $phone_fields = ['Mobile', 'Phone', 'Fax'];

        $this->truncate('client');
        $this->truncate('utilityprovider');

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
                    $val = trim(strtr($imp[$from], ['/' => '', '.' => '']));
                }
                // Location Street type
                elseif ('LocationStreet' == $to) {
                   list($val, $st) = $this->streetFix($imp[$from]);
                   $p->setLocationStreetType($st);
                }
                // convert ID-s to the parameter system
                elseif('EcActivity' == $to || 'MaritalStatus' == $to || 'EducationCode' == $to) {
                    $val = $this->convertId($to, $imp[$from]);
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
                $upid = new Utilityprovider();
                $upid->setValue($val);
                $upid->setClient($client);
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
            ],
            'EducationCode' => [
                2 => 8,
                3 => 9,
                4 => 10,
                5 => 11,
                6 => 12,
                7 => 13,
                8 => 14,
                9 => 15
            ],
            'EcActivity' => [
                2 => 16,
                3 => 17,
                4 => 18,
                5 => 19,
                6 => 20,
                7 => 21,
                8 => 22,
                9 => 23,
                10 => 24,
                11 => 25,
                12 => 26,
                13 => 27,
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
