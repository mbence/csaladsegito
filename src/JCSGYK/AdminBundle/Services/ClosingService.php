<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

use JCSGYK\AdminBundle\Entity\MonthlyClosing;
use JCSGYK\AdminBundle\Entity\Invoice;

/**
 * Monthly Closing Service
 */
class ClosingService
{
    /** Service container */
    private $container;

    /** data array for the exports */
    private $files = [];

    /** format of export files */
    private $exportFormat;

    /** list of client ids, who are already in the customer file */
    private $clients_added = [];

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;

        // TODO: make this company dependent!
        $this->setExportFormat();
    }

    /**
     * returns a list of the latest closing records
     * @return type
     */
    public function getList()
    {
        $em = $this->container->get('doctrine')->getManager();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();

        return $em->createQuery("SELECT c FROM JCSGYKAdminBundle:MonthlyClosing c WHERE c.companyId = :company_id ORDER BY c.createdAt DESC")
            ->setParameter('company_id', $company_id)
            ->setMaxResults(20)
            ->getResult();
    }

    /**
     * Start the monthly closing process
     * @param int $period 1 = normal run (next month), 0 = actual month
     * @return \JCSGYK\AdminBundle\Entity\MonthlyClosing
     */
    public function run($period = 1)
    {
        $em = $this->container->get('doctrine')->getManager();
        $sec = $this->container->get('security.context');
        $user = $sec->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $summary = '';

        // set the start / end dates
        // start date is next months first day
        if (1 == $period) {
            // next month
            $start = new \DateTime('first day of next month');
            $end = new \DateTime('last day of next month');
        }
        else {
            // actual month
            $start = new \DateTime('+2 day');
            $end = new \DateTime('last day of this month');
        }
        $created_at = new \DateTime();

        $summary .= "Havi zárás \n";
        $summary .= sprintf("%s - %s \n\n", $start->format('Y-m-d'), $end->format('Y-m-d'));
        $summary .= sprintf("%s: Indítva \n", $created_at->format('H:i:s'));

        // create a new closing record
        $closing = new MonthlyClosing();
        $closing->setCompanyId($company_id);
        $closing->setCreator($user);
        $closing->setCreatedAt($created_at);
        $closing->setStatus(MonthlyClosing::RUNNING);
        $closing->setStartDate($start);
        $closing->setEndDate($end);
        $closing->setSummary($summary);

        $em->persist($closing);
        $em->flush();

        // find all clients that have active subscriptions
        $clients = $em->getRepository('JCSGYKAdminBundle:Client')->getForClosing($company_id);
        $summary .= sprintf("%s: %s ügyfél lekérdezve\n", date('H:i:s'), count($clients));
        $closing->setSummary($summary);
        $em->flush();

        // create the invoices
        $invoice_count = 0;
        $invocie_service = $this->container->get('jcs.invoice');
        foreach ($clients as $client) {
            $invoice = $invocie_service->create($client, clone $start, clone $end);
            if (!empty($invoice)) {
                $invoice_count ++;
            }
        }
        if (empty($invoice_count)) {
            $summary .= sprintf("%s: Nincsen új megrendelés \n", date('H:i:s'));
        }
        else {
            $summary .= sprintf("%s: %s db számla kiállítva \n", date('H:i:s'), $invoice_count);
        }

        // create the EcoSTAT files
        $exp = $this->export();
        if (!empty($exp)) {
            $summary .= sprintf("%s: EcoStat fájlok létrehozva \n", date('H:i:s'));

            // Send the EcoSTAT files to bookkeeping
            $this->writeFiles();
        }

        // update the closing record
        $summary .= sprintf("%s: Befejezve\n", date('H:i:s'));

        $closing->setSummary($summary);
        $closing->setStatus(MonthlyClosing::SUCCESS);
        $em->flush();

        return $closing;
    }


    /**
     * Creates the EcoStat export files in $this->files from the unsent invoices
     */
    public function export()
    {
        $em = $this->container->get('doctrine')->getManager();
        $sec = $this->container->get('security.context');
        $user = $sec->getToken()->getUser();
        $company_id = $this->container->get('jcs.ds')->getCompanyId();
        $invocie_service = $this->container->get('jcs.invoice');

        $result = 0;

        // find the unsent invocies in batches
        $offset = 0;
        $limit = 10;
        $invoices = $invocie_service->getInvoices($company_id, $limit, $offset);
        while (!empty($invoices)) {
            // process the invoce
            foreach ($invoices as $invoice) {
                $result += $this->exportInvoice($invoice);

                // set the invoice status to open (data exported to EcoSTAT)
                //$invoice->setStatus(Invoice::OPEN);
            }
            $em->flush();

            // get the next batch
            if (count($invoices) == $limit) {
                $offset += $limit;
                $invoices = $invocie_service->getInvoices($company_id, $limit, $offset);
            }
            else {
                $invoices = [];
            }
        }

        return $result;
    }

    /**
     * Adds the data of one invoice to the export files
     * @param Invoice $invoice
     * @return int result
     */
    private function exportInvoice(Invoice $invoice)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        $client = $invoice->getClient();
        $city = substr($client->getCity(), 0, 16);
        $city2 = substr($client->getCity(), 16);

        $vat = 0.27;
        // deadline is the 5th day of next month
        $deadline = clone $invoice->getEndDate();
        $deadline = $deadline->modify('first day of next month')->format('Ymd');

        $comment = sprintf('%s. havi étkeztetés', $invoice->getEndDate()->format('n'));

        $net_amount = round($invoice->getAmount() * (1 - $vat));

        $data = [
            'szlaatf.txt' => [
                'BSZAM'         => $invoice->getId(),
                'PARTNERKOD'    => $invoice->getClient()->getId(),
                'IKTATDAT'      => $deadline,
                'SZLADAT'       => $deadline,
                'TELJDAT'       => $deadline,
                'FIZHATIDO'     => $deadline,
                'MEGJ'          => $comment,
                'NETTO3'        => $net_amount,
                'AFA3'          => $invoice->getAmount() - $net_amount,
                'VEGOSSZEG'     => $invoice->getAmount(),
            ],
            'szlaatt.txt'   => [],
            'vevo.txt'      => [],
        ];

        if (!in_array($client->getId(), $this->clients_added)) {
            $data['vevo.txt'] = [
                'PARTNERKOD'    => $invoice->getClient()->getId(),
                'PARTNERNEV'    => $ae->formatName($client->getFirstname(), $client->getLastname(), $client->getTitle()),
                'IRANYITOSZAM'  => $invoice->getClient()->getZipCode(),
                'VAROS'         => $city,
                'CIM'           => $ae->formatAddress('', '', $client->getStreet(), $client->getStreetType(), $client->getStreetNumber(), $client->getFlatNumber()),
                'TELEFONSZAM'   => $client->getPhone(),
                'FAXSZAM'       => $client->getFax(),
                'VAROS2'        => $city2,
            ];
            $this->clients_added[] = $client->getId();
        }

        $items = json_decode($invoice->getItems(), true);
        if (!empty($items)) {
            foreach ($items as $item) {
                $data['szlaatt.txt'][] = [
                    'BSZAM'     => $invoice->getId(),
                    'NEV'       => $item['name'],
                    'MENNY'     => $item['quantity'],
                    'EGYSAR'    => $item['unit_price'],
                    'ERTEK'     => $item['value'],
                ];
            }
        }

        $res = $this->addExportLine($data);

        return $res;
    }

    private function addExportLine($data)
    {
        $result = 0;
        // walk through the files
        foreach ($this->exportFormat as $file => $fields) {
            // some files can have more then 1 lines (like items)
            if ('szlaatt.txt' == $file) {
                foreach ($data[$file] as $item) {
                    $result = $this->addline($file, $item);
                }
            }
            else {
                $result = $this->addline($file, $data[$file]);
            }
        }

        return $result;
    }

    private function addLine($file, $data)
    {
        $result = 0;
        // loop through the fields
        foreach ($this->exportFormat[$file] as $field) {
            $line = '';
            $value = '';
            // check input data
            if (isset($data[$field[0]])) {
                $value = $data[$field[0]];
            }
            else {
                $value = $field[3];
            }
            // format the numeric values
            if (!empty($field[2])) {
                $value = number_format($value, $field[2], '.', '');
            }
            // pad to the required length
            $value = $this->mb_str_pad($value, $field[1], " ", STR_PAD_RIGHT);

            // add to the file contents
            if (!isset($this->files[$file])) {
                $this->files[$file] = '';
            }
            $this->files[$file] .= $value;

            $result = 1;
        }

        // new line at the end
        $this->files[$file] .= "\n";

        return $result;
    }

    private function mb_str_pad ($input, $pad_length, $pad_string, $pad_style, $encoding = "UTF-8")
    {
       return str_pad($input, strlen($input) - mb_strlen($input,$encoding) + $pad_length, $pad_string, $pad_style);
    }

    private function setExportFormat()
    {
        /*
        A CT-EcoSTAT Pénzügyi rendszer számlákat text file-ból, valamint dbf állományból tud átvenni.
        Négy file-ból veszi át az adatokat: 1. szlaatf.dbf / szlataf.txt            : számla fej adatok
                                                          2. szlaatt.dbf / szlaatt.txt           : számla tételek
                                                          3. szlaatkot.dbf / szlaatkot.txt    : számla típus megbontás
                                                          4. szlaatm.dbf / szlaatm.txt        : számla megjegyzések

        Az átadó file-ok szerkezete itt található.
        Ha a PARTNERKOD vagy a BSZAM mező rövid lenne az adatok átadására, akkor használható a "Módosított struktúra" lapon található formátum..
        Itt annyi az eltérés, hogy a file-ok neve: szlaatf2.txt, szlaatt2.txt, szlaatm2.txt. A BSZAM mező 50 karakter hosszú és a PARTNERKOD mező 8 karakter lett.
        Az új struktúra csak a text file-okkal működik jelenleg.
        */

        // pretty dirty temporary data for JSZSZGYK
        $this->exportFormat = [
            'szlaatf.txt' => [
                //      Field name		Width	Dec	Default
                //      ==========		=====	===	====
                1  => ['BSZAM',                 10,	0,      ''],     // Az átadó rendszerbeli sorszám, a mi pénzügyi rendszerünkben ez lesz a külső sorszám
                2  => ['PARTNERKOD',		6,	0,      ''],
                3  => ['BANKNEV',		40,	0,      'Sberbenk Magyarország Zrt.'],
                4  => ['BANKSZLA',		26,	0,      '14100309-18423949-01000003'],
                5  => ['IKTATDAT',		8,	0,      ''],     // Formátuma: YYYYMMDD  pl.:20041028
                6  => ['SZLADAT',		8,	0,      ''],     // Formátuma: YYYYMMDD
                7  => ['TELJDAT',		8,	0,      ''],     // Formátuma: YYYYMMDD
                8  => ['FIZHATIDO',		8,	0,      ''],     // mind a négynél hónap 5. napja​
                9  => ['FIZMOD',		15,	0,      'Készpénz'],     // Ha az első karakter='K' akkor készpénzes számlaként kerül átvételre. (pl.: Készpénz)
                10 => ['MEGJ',                  60,	0,      ''],     //Ide be lehetne írni, hogy melyik havi étkezés​
                11 => ['ALAP_NEM',		12,	0,      ''],     // üres​         Formátuma: 999999999999
                12 => ['ADOMENTES',		12,	0,      ''],     // üres​         Formátuma: 999999999999
                13 => ['NETTO1',		12,	0,      ''],     // üres​         5%-os nettó. Formátuma: 999999999999
                14 => ['NETTO2',		12,	0,      ''],     // üres​         18%-os nettó. Formátuma: 999999999999
                15 => ['NETTO3',		12,	0,      ''],     // 27%-os nettó. Formátuma: 999999999999        // ez kell
                16 => ['AFA2',                  9,	0,      ''],     // üres​         18%-os áfa. Formátuma: 999999999
                17 => ['AFA3',                  9,	0,      ''],     // 27%-os áfa. Formátuma: 999999999             // ez kell
                18 => ['VEGOSSZEG',		13,	0,      ''],     // Formátuma: 9999999999999
                19 => ['BELSO',                 11,	0,      ''],     // Üresen kell hagyni.
                20 => ['AFA1',                  9,	0,      ''],     // üres​       5%-os áfa. Formátuma: 999999999
                21 => ['EREDETI',		1,	0,      ''],     // üres​         Értéke T vagy F
                22 => ['HELYESBITO',		11,	0,      ''],     // üres​          A másik számla (helyesbítő pár) BSZAM mezője.
                23 => ['KULCSSZO',		60,	0,      'Étkeztetés'],       // A számlához eltárolt kulcsszó.
                24 => ['GAZDKOD',		20,	0,      '300​'],              // A gazdálkodó kódja.
                /*
                 * Az EREDETI, HELYESBITO mező csak helyesbítő vagy stornó számlák esetén használt.
                 * Az EREDETI mező jelöli, hogy melyik az eredeti számla, ami helyesbítve lett. Az eredeti számlánál T (true), a helyesbitő számlánál F.
                 * A HELYESBITO mező jelöli, hogy a számlának mi a helyesbítő párja.
                 * EREDETI mező ha S-t tartlamaz akkor ez egy stornó számla és a HELYESBITO mezőben van, hogy mi az eredeti.
                 */
            ],
            'szlaatt.txt' => [
                // A számlához tartozó tételek vannak benne.
                //      Field name              Width	Dec	Default
                //      ==========              =====	===	=======
                1  => ['BSZAM',                 10,	0,	''],	// Az átadó rendszerbeli sorszám ez a kapcsolómező a szlaatf-hez
                2  => ['KSH',                   15,	0,	''],	// üres​         Kitöltése nem kötelző.
                3  => ['NEV',                   60,	0,      ''],
                4  => ['AFA',                   2,	0,	'27'],	// Lehetséges értékei: NK (adóalapot nem képező), AM (adómentes), 5, 15, 18, 25, 20
                5  => ['MEGYS',                 3,	0,	'Nap​'],	// Mennyiségi egység
                6  => ['MENNY',                 14,	6,	''],	// Formátuma: 9999999.999999
                7  => ['EGYSAR',                13,	2,	''],	// Nettó egységár (Formátuma: 9999999999.99)
                8  => ['ERTEK',                 13,	2,	''],	// Nettó érték  (Formátuma: 9999999999.99)
                9  => ['ALAPFKSZAM',		12,	0,	''],    // üres​ 	Kitöltése nem kötelző.Ha a főkönyvi rendszerben automatikusan könyvelésre fel szeretnénk adni a számlákat, akkor ki kell tölteni. A számla alapokat erre a főkönyvi (9-es) számra könyveljük le.
                10 => ['AFAFKSZAM',		12,	0,	''],    // üres​ 	Kitöltése nem kötelző.Ha a főkönyvi rendszerben automatikusan könyvelésre fel szeretnénk adni a számlákat, akkor ki kell tölteni.Áfa (9-es)főkönyvi számra történik a főkönyvi programban az automatikus könyvelés..
                11 => ['GYUJTOKOD',		12,	0,	'321230000​'],    // 	Kitöltése nem kötelző.
                12 => ['ROVAT',                 8,	0,	''],	// üres​         Kitöltése nem kötelző.
                13 => ['FELADAT',		10,	0,	'40104-K​'],    // 	Kitöltése nem kötelző.
                14 => ['TARTOZIK',		20,	0,	''],    // üres​ 	A kontírozáson megjelenő tartozik számlaszám
                15 => ['KOVETEL',		20,	0,	''],    // üres​ 	A kontírozáson megjelenő követel számlaszám
                16 => ['MASODLAGOS',		20,	0,	''],    // üres​ 	A kontírozáson megjelenő másodlagos számlaszám
            ],
            'vevo.txt' => [
                // Az átadott számlákhoz tartozó vevők vagy szállítók.
                //      Field name		Width	Dec	Default
                //      ==========		=====	===	=======
                1  => ['PARTNERKOD',		6,	0,	''],    // U.a. mint a szlaatf.txt-ben a PARTNERKOD mező
                2  => ['PARTNERNEV',		40,	0,	''],    //
                3  => ['IRANYITOSZAM',		8,	0,	''],    //
                4  => ['VAROS',                 16,	0,	''],    //
                5  => ['CIM',                   30,	0,	''],    //
                6  => ['ADOSZAM',		14,	0,	''],    //
                7  => ['UGYINTEZO',		20,	0,	''],    //
                8  => ['TELEFONSZAM',		15,	0,	''],    //
                9  => ['FAXSZAM',		15,	0,	''],    //
                10 => ['BANKNEVE',		25,	0,	''],    //
                11 => ['BANKSZAM',		26,	0,	''],    //
                12 => ['VAROS2',		40,	0,	''],    // A város mező folytatása, ha a16 karakterben nem férne el a város neve
                13 => ['AHT',                   1,	0,	''],    // Értéke I/N, annak megfelelően, hogy ÁHT-n belüli partnerről van szó
                /*
                A szallito.txt tartalmát mint szállítókat fogja átvenni a program, a vevo.txt tartalmát mint vevőket
                fogja átvenni. Ha egy partner lehet vevő is és szállító is, akkor mindkét állományba bele kell rakni.
                Csak azokat a partnereket kell átadni, akik meg nem szerepelnek az Eco-STAT rendszeben.
                Ha egy átadott partnerkód már szerepel az CT-EcoSTAT rendszerben, akkor annak minden adata felül lesz írva az átadott új értékekkel!
                */
            ],
        ];
    }

    private function writeFiles()
    {
        $res = 0;
        $folder = $this->container->get('kernel')->getRootDir() . '/../web/files/';
        foreach ($this->files as $file => $contents) {
            $filename = $folder . $file;
            file_put_contents($filename, $contents);
            $res ++;
        }

        return $res;
    }
}

