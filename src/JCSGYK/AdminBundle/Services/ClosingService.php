<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

use JCSGYK\AdminBundle\Entity\MonthlyClosing;
use JCSGYK\AdminBundle\Entity\Invoice;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Services\InvoiceService;

/**
 * Monthly Closing Service
 */
class ClosingService
{
    /** Service container */
    private $container;

    /** Datastore */
    private $ds;

    /** data array for the exports */
    private $files = [];

    /** format of export files */
    private $exportFormat;

    /** list of client ids, who are already in the customer file */
    private $clients_added = [];

    /** where to store tmp files */
    private $tmp_folder;

    /** Command output Interface
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /** Process summary text */
    private $summary = '';


    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->ds = $this->container->get('jcs.ds');

        // TODO: make this company dependent!
        $this->setExportFormat();
        $this->tmp_folder = $this->container->get('kernel')->getRootDir() . '/cache/tmp/' . uniqid() . '/';
    }

    /**
     * returns a list of the latest closing records
     * @return type
     */
    public function getList()
    {
        $em = $this->container->get('doctrine')->getManager();
        $company_id = $this->ds->getCompanyId();

        return $em->createQuery("SELECT c.id, c.companyId, c.startDate, c.endDate, c.status, c.createdAt, c.summary FROM JCSGYKAdminBundle:MonthlyClosing c WHERE c.companyId = :company_id ORDER BY c.createdAt DESC")
            ->setParameter('company_id', $company_id)
            ->setMaxResults(20)
            ->getResult();
    }

    private function output($text)
    {
        $this->summary .= $text . "\n";

        if (!empty($this->output)) {
            $this->output->writeln($text);
        }
    }

    /**
     * Start the monthly closing process
     * @param $closing_type 1 monthly, 2 daily or 3 home-help
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return \JCSGYK\AdminBundle\Entity\MonthlyClosing
     */
    public function run($closing_type, OutputInterface $output = null)
    {
        $this->output = $output;

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        /** @var DataStore $ds */
        $ds = $this->container->get('jcs.ds');
        $user = $ds->getUser();
        $company_id = $ds->getCompanyId();

        // get the start / end dates and the title
        list($start, $end, $process_title) = $this->getPeriod($closing_type);
        $created_at = new \DateTime();

        $this->output($process_title);
        $this->output(sprintf("%s - %s \n", $start->format('Y-m-d'), $end->format('Y-m-d')));
        $this->output(sprintf("%s: Indítva", $created_at->format('H:i:s')));

        // create a new closing record
        $closing = (new MonthlyClosing())
            ->setCompanyId($company_id)
            ->setCreator($user)
            ->setCreatedAt($created_at)
            ->setStatus(MonthlyClosing::RUNNING)
            ->setStartDate($start)
            ->setEndDate($end)
            ->setSummary($this->summary)
            ->setClosingtype($closing_type);

        $em->persist($closing);
        $em->flush();

        // find all clients that have active subscriptions
        // for daily closing only select the clients that are new and dont have an invoice yet
        $clients = $em->getRepository('JCSGYKAdminBundle:Client')->getForClosing($company_id, $closing_type);
        $this->output(sprintf("%s: %s ügyfél lekérdezve", date('H:i:s'), count($clients)));
        $closing->setSummary($this->summary);
        $em->flush();

        // create the invoices
        $client_count = 0;
        $invoice_count = 0;
        /** @var InvoiceService $invoice_service */
        $invoice_service = $this->container->get('jcs.invoice');
        foreach ($clients as $client) {
            $invoice = $invoice_service->create($client, clone $start, clone $end, $closing_type);
            if (!empty($invoice)) {
                $invoice_count ++;
            }
            $client_count++;
            if ($client_count % 100 == 0) {
                $this->output(sprintf("%s: %s ügyfél feldolgozva", date('H:i:s'), $client_count));
                $closing->setSummary($this->summary);
            }
        }
        if (empty($invoice_count)) {
            $this->output(sprintf("%s: Nincsen új megrendelés", date('H:i:s')));
        }
        else {
            $this->output(sprintf("%s: %s db számla kiállítva", date('H:i:s'), $invoice_count));
        }

        // create the EcoSTAT files
        $exp = $this->export();
        if (!empty($exp)) {
            $this->output(sprintf("%s: EcoStat fájlok létrehozva", date('H:i:s')));

            // close the output files
            $this->closeFiles();
            // compress the output files
            $zip = $this->zipFiles($closing_type);
            $zip_file_contents = file_get_contents($zip);
            $this->deleteFiles($zip);

            // save the zip file in the monthlyClosing record
            $closing->setFiles($zip_file_contents);
            $closing->setSummary($this->summary);
            $em->flush();

            // Send the EcoSTAT files to bookkeeping
            //$this->writeFiles();
            $mail_ok = $this->sendMails($start, $end, basename($zip), $zip_file_contents);
            if ($mail_ok) {
                $this->output(sprintf("%s: Email sikeresen kiküldve", date('H:i:s')));
            }
            else {
                $this->output(sprintf("%s: Email hiba!", date('H:i:s')));
            }

            // update the client balances
            $invoice_service->bulkUpdateBalance();
        }

        // update the closing record
        $this->output(sprintf("%s: Befejezve", date('H:i:s')));

        $closing->setSummary($this->summary);
        $closing->setStatus(MonthlyClosing::SUCCESS);
        $em->flush();

        return $closing;
    }


    private function updateBalances($clients)
    {
        $invoice_service = $this->container->get('jcs.invoice');
        foreach ($clients as $client) {
            // update the client balance
            $invoice_service->updateBalance($client->getCatering());
        }
    }

    /**
     * Creates the EcoStat export files in $this->files from the unsent invoices
     */
    public function export()
    {
        $em = $this->container->get('doctrine')->getManager();
        $company_id = $this->ds->getCompanyId();
        $invoice_service = $this->container->get('jcs.invoice');

        $result = 0;

        // find the unsent invocies in batches
        $invoices = $invoice_service->getInvoices($company_id);
        // process the invoice
        foreach ($invoices as $invoice) {
            // only export invoices with amount to pay
            if ($invoice->getAmount() != 0) {
                $result += $this->exportInvoice($invoice);
            }
            else {
                $invoice->setStatus(Invoice::CLOSED);
            }
        }
        $em->flush();

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

        $vat = $this->ds->getVat();

        // deadline is the 5th day of next month
        $deadline = clone $invoice->getStartDate();
        $deadline = $deadline->modify('+4 days')->format('Ymd');

        $title = MonthlyClosing::HOMEHELP == $invoice->getInvoicetype() ? 'gondozás' : 'étkeztetés';
        if (empty($invoice->getCancelId())) {
            $comment = sprintf('%s. havi %s', $invoice->getEndDate()->format('n'), $title);
        }
        else {
            $comment = sprintf('%s számla sztornó (%s. hó)', $invoice->getCancelId(), $invoice->getEndDate()->format('n'));
        }

        $gross_amount = $invoice->getAmount();
        $net_amount = round($gross_amount / (1 + $vat));

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
                'AFA3'          => $gross_amount - $net_amount,
                'VEGOSSZEG'     => $gross_amount,
                'KULCSSZO'      => ucfirst($title),
            ],
            'szlaatt.txt'   => [],
            'vevo.txt'      => [],
        ];

        // cancelling invoices
        if (!empty($invoice->getCancelId())) {
            $data['szlaatf.txt']['EREDETI']    = 'S';
            $data['szlaatf.txt']['HELYESBITO'] = $invoice->getCancelId();
        }

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
                $data_item = [
                    'BSZAM'     => $invoice->getId(),
                    'NEV'       => $item['name'],
                    'MENNY'     => $item['quantity'],
                    'EGYSAR'    => $item['net_price'],
                    'ERTEK'     => $item['net_value'],
                ];
                if (isset($item['unit'])) {
                    $data_item['MEGYS'] = $item['unit'];
                }
                $data['szlaatt.txt'][] = $data_item;
            }
        }

        $res = $this->addExportLine($data);

        if ($res) {
            // set the invoice status to open (data exported to EcoSTAT)
            $new_status = empty($invoice->getCancelId()) ? Invoice::OPEN : Invoice::CLOSED;
            $invoice->setStatus($new_status);
        }

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
        $line = '';

        // loop through the fields
        foreach ($this->exportFormat[$file] as $field) {
            $value = '';
            // check input data
            if (isset($data[$field[0]])) {
                $value = $data[$field[0]];
            }
            else {
                $value = trim($field[3]);
            }
            // format the numeric values
            if (!empty($field[2])) {
                $value = number_format($value, $field[2], '.', '');
            }
            // pad to the required length
            $value = $this->mb_str_pad($value, $field[1], " ", STR_PAD_RIGHT);

            // add to the file contents
            $line .= $value;

            $result = 1;
        }

        // new line at the end
        $line .= "\n";

        // convert to ISO-8859-2
        $line = mb_convert_encoding($line, 'ISO-8859-2', 'UTF-8');

        // write it out!
        $this->writeFile($file, $line);

        return $result;
    }

    private function writeFile($file, $line) {
        // create the tmp dir if needed
        if (!is_dir($this->tmp_folder)) {
            mkdir($this->tmp_folder, 0700, true);
        }
        // create and open the file
        if (empty($this->files[$file])) {
            $handle = fopen($this->tmp_folder . $file,"w+");
            if (false === $handle) {
                throw new HttpException(500, 'File write error');
            }
            $this->files[$file] = $handle;
        }

        // write the contents
        return fwrite($this->files[$file], $line);
    }

    /**
     * Close the tmp files
     */
    private function closeFiles()
    {
        foreach ($this->files as $file => $handle) {
            fclose($handle);
        }
    }

    /**
     * Compress the files in $this->files
     * @return zip  file name
     * @throws HttpException
     */
    private function zipFiles($closing_type)
    {
        $title = MonthlyClosing::HOMEHELP == $closing_type ? 'gondozas' : 'etkeztetes';
        $zip = new \ZipArchive();
        $zipfilename = $this->tmp_folder . $title . '_import_' . date('Ymd') . '.zip';

        if ($zip->open($zipfilename, \ZipArchive::CREATE) === false) {
            throw new HttpException(500, 'Zip file write error');
        }
        else {
            foreach ($this->files as $file => $handle) {
                $realfile = $this->tmp_folder . $file;
                $zip->addFile($realfile, $file);
            }
            $zip->close();
        }

        return $zipfilename;
    }

    /**
     * Deletes the tmp files (from $this->files and the zip)
     * @param string $zip
     */
    private function deleteFiles($zip)
    {
        foreach ($this->files as $file => $handle) {
            $f = $this->tmp_folder . $file;
            if (file_exists($f)) {
                unlink($f);
            }
        }

        if (file_exists($zip)) {
            unlink($zip);
        }

        rmdir($this->tmp_folder);
    }

    /**
     * multibyte str_pad
     */
    private function mb_str_pad ($input, $pad_length, $pad_string, $pad_style, $encoding ="UTF-8")
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
        Ha a PARTNERKOD vagy a BSZAM mező rövid lenne az adatok átadására, akkor használható a"Módosított struktúra" lapon található formátum..
        Itt annyi az eltérés, hogy a file-ok neve: szlaatf2.txt, szlaatt2.txt, szlaatm2.txt. A BSZAM mező 50 karakter hosszú és a PARTNERKOD mező 8 karakter lett.
        Az új struktúra csak a text file-okkal működik jelenleg.
        */

        // pretty dirty temporary data for JSZSZGYK
        $this->exportFormat = [
            'szlaatf.txt' => [
                //      Field name		Width	Dec	Default
                //      ==========		=====	===	====
                1  => ['BSZAM',                 10,	0,      '0'],     // Az átadó rendszerbeli sorszám, a mi pénzügyi rendszerünkben ez lesz a külső sorszám
                2  => ['PARTNERKOD',		6,	0,      '0'],
                3  => ['BANKNEV',		40,	0,      ''],        // Sberbenk Magyarország Zrt.
                4  => ['BANKSZLA',		26,	0,      ''],        // 14100309-18423949-01000003
                5  => ['IKTATDAT',		8,	0,      ''],     // Formátuma: YYYYMMDD  pl.:20041028
                6  => ['SZLADAT',		8,	0,      ''],     // Formátuma: YYYYMMDD
                7  => ['TELJDAT',		8,	0,      ''],     // Formátuma: YYYYMMDD
                8  => ['FIZHATIDO',		8,	0,      ''],     // mind a négynél hónap 5. napja​
                9  => ['FIZMOD',		15,	0,      'Készpénz'],     // Ha az első karakter='K' akkor készpénzes számlaként kerül átvételre. (pl.: Készpénz)
                10 => ['MEGJ',                  60,	0,      ''],     //Ide be lehetne írni, hogy melyik havi étkezés​
                11 => ['ALAP_NEM',		12,	0,      '0'],     // üres​         Formátuma: 999999999999
                12 => ['ADOMENTES',		12,	0,      '0'],     // üres​         Formátuma: 999999999999
                13 => ['NETTO1',		12,	0,      '0'],     // üres​         5%-os nettó. Formátuma: 999999999999
                14 => ['NETTO2',		12,	0,      '0'],     // üres​         18%-os nettó. Formátuma: 999999999999
                15 => ['NETTO3',		12,	0,      '0'],     // 27%-os nettó. Formátuma: 999999999999        // ez kell
                16 => ['AFA2',                  9,	0,      '0'],     // üres​         18%-os áfa. Formátuma: 999999999
                17 => ['AFA3',                  9,	0,      '0'],     // 27%-os áfa. Formátuma: 999999999             // ez kell
                18 => ['VEGOSSZEG',		13,	0,      '0'],     // Formátuma: 9999999999999
                19 => ['BELSO',                 11,	0,      ''],     // Üresen kell hagyni.
                20 => ['AFA1',                  9,	0,      '0'],     // üres​       5%-os áfa. Formátuma: 999999999
                21 => ['EREDETI',		1,	0,      ''],     // üres​         Értéke T vagy F (S - sztornó)
                22 => ['HELYESBITO',		11,	0,      ''],     // üres​          A másik számla (helyesbítő pár) BSZAM mezője.
                23 => ['KULCSSZO',		60,	0,      'Étkeztetés'],       // A számlához eltárolt kulcsszó.
                24 => ['GAZDKOD',		20,	0,      '300'],              // A gazdálkodó kódja.
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
                1  => ['BSZAM',                 10,	0,	'0'],	// Az átadó rendszerbeli sorszám ez a kapcsolómező a szlaatf-hez
                2  => ['KSH',                   15,	0,	''],	// üres​         Kitöltése nem kötelző.
                3  => ['NEV',                   60,	0,      ''],
                4  => ['AFA',                   2,	0,	'27'],	// Lehetséges értékei: NK (adóalapot nem képező), AM (adómentes), 5, 15, 18, 25, 20
                5  => ['MEGYS',                 3,	0,	'Nap'],	// Mennyiségi egység
                6  => ['MENNY',                 14,	6,	'0'],	// Formátuma: 9999999.999999
                7  => ['EGYSAR',                13,	2,	'0'],	// Nettó egységár (Formátuma: 9999999999.99)
                8  => ['ERTEK',                 13,	2,	'0'],	// Nettó érték  (Formátuma: 9999999999.99)
                9  => ['ALAPFKSZAM',		12,	0,	''],    // üres​ 	Kitöltése nem kötelző.Ha a főkönyvi rendszerben automatikusan könyvelésre fel szeretnénk adni a számlákat, akkor ki kell tölteni. A számla alapokat erre a főkönyvi (9-es) számra könyveljük le.
                10 => ['AFAFKSZAM',		12,	0,	''],    // üres​ 	Kitöltése nem kötelző.Ha a főkönyvi rendszerben automatikusan könyvelésre fel szeretnénk adni a számlákat, akkor ki kell tölteni.Áfa (9-es)főkönyvi számra történik a főkönyvi programban az automatikus könyvelés..
                11 => ['GYUJTOKOD',		12,	0,	'321230000'],    // 	Kitöltése nem kötelző.
                12 => ['ROVAT',                 8,	0,	''],	// üres​         Kitöltése nem kötelző.
                13 => ['FELADAT',		10,	0,	'40104-K'],    // 	Kitöltése nem kötelző.
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

    /**
     * Send the generated files to bookkeeping
     */
    private function sendMails(\DateTime $start_date, \DateTime $end_date, $zip, &$zip_file_contents)
    {
        if (!empty($zip)) {

            $ae = $this->container->get('jcs.twig.adminextension');

            $subject = sprintf('Havi számla import: %s - %s', $ae->formatDate($start_date), $ae->formatDate($end_date));
            $mailer_from = 'oszirozsaebed@gmail.com';
            $mailer_from_name = 'JSzSzGyK-Hsz';

            $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom([$mailer_from => $mailer_from_name])
            ->addTo('penzugy@jszszgyk.hu')
            ->addCC('gazdasag@jszszgyk.hu')
            //->addCC('mxbence@gmail.com')
            ->setBody("Tisztelt Pénzügy!\n\n Mellékelve küldjük a számlák importálásához szükséges fájlokat.", 'text/plain');

            // add attachment
            $attachment = \Swift_Attachment::newInstance($zip_file_contents, $zip, 'application/zip');
            $message->attach($attachment);

            $res = $this->container->get('mailer')->send($message);

            return $res;
        }
        else {
            return false;
        }
    }

    /**
     * Returns the start and end dates of the period depending of the close type (daily, monthly or home-help)
     * @param $closing_type
     * @return array ($start, $end, $process_title)
     */
    private function getPeriod($closing_type)
    {
        // home help is always the previous month
        if (MonthlyClosing::HOMEHELP == $closing_type) {
            $start = new \DateTime('first day of last month');
            $end = new \DateTime('last day of last month');
            $process_title = 'Gondozás zárás';
        }
        elseif (MonthlyClosing::MONTHLY == $closing_type) {
            // next month
            $start = new \DateTime('first day of next month');
            $end = new \DateTime('last day of next month');
            $process_title = 'Havi zárás';
        } else {
            // actual month
            $start = new \DateTime('+1 day');
            $process_title = 'Napi zárás';
            // after the monthly closing, the daily closing also must create the orders and invoice for the next month
            if (date('j') < 25) {
                $end = new \DateTime('last day of this month');
            } else {
                $end = new \DateTime('last day of next month');
            }
        }

        return array($start, $end, $process_title);
    }
}

