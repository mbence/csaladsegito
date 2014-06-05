<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Console\Output\OutputInterface;

use JCSGYK\AdminBundle\Entity\DailyOrder;
use JCSGYK\AdminBundle\Entity\Invoice;

/**
 * Daily Orders Service
 */
class DailyOrdersService
{
    /** Service container */
    private $container;

    /** Datastore */
    private $ds;

    /** where to store tmp files */
    private $tmp_folder;

    /** Command output Interface*/
    private $output;

    /** Process summary text
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    private $summary = '';

    /** Columns of the xls table */
    private $colunms = [];

    /** Data array of the daily orders */
    private $orders = [];

    /** cost of 1 lunch */
    private $menuCost;

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
        $this->ds = $this->container->get('jcs.ds');

        $this->tmp_folder = $this->container->get('kernel')->getRootDir() . '/cache/tmp/' . uniqid() . '/';
        $this->menuCost = $this->ds->getMenuCost();
    }

    /**
     * returns a list of the latest closing records
     * @return type
     */
    public function getList()
    {
        $em = $this->container->get('doctrine')->getManager();
        $company_id = $this->ds->getCompanyId();

        return $em->createQuery("SELECT d.id, d.companyId, d.startDate, d.endDate, d.status FROM JCSGYKAdminBundle:DailyOrder d WHERE d.companyId = :company_id ORDER BY d.createdAt DESC")
            ->setParameter('company_id', $company_id)
            ->setMaxResults(31)
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
     * Start the daily orders process
     * Or if the end date is present, then send the weekly order summary
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \DateTime $date   day of the order or the start date of the week
     * @param \DateTime $end_date  end date of the week
     * @return \JCSGYK\AdminBundle\Entity\DailyOrder
     */
    public function run(OutputInterface $output = null, \DateTime $date = null, \DateTime $end_date = null)
    {
        // reset
        $this->output = $output;
        $this->summary = '';
        $this->colunms = [];
        $this->orders = [];

        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        $user = $this->ds->getUser();
        $company_id = $this->ds->getCompanyId();

        // set the dates
        if (empty($date)) {
            $date = new \DateTime('tomorrow');
        }
        $created_at = new \DateTime();

        $this->output(empty($end_date) ? 'Konyhai megrendelés' : 'Heti számla összesítő');
        $this->output(empty($end_date) ?
                sprintf("%s \n", $ae->formatDate($date, 'fd'))
                :
                sprintf("%s - %s \n", $ae->formatDate($date, 'fd'), $ae->formatDate($end_date, 'fd'))
        );
        $this->output(sprintf("%s: Indítva", $created_at->format('H:i:s')));

        // create a new closing record
        $order = new DailyOrder();
        $order->setCompanyId($company_id);
        $order->setCreator($user);
        $order->setCreatedAt($created_at);
        $order->setStatus(DailyOrder::RUNNING);
        $order->setStartDate($date);
        $order->setEndDate($end_date);
        $order->setSummary($this->summary);

        $em->persist($order);
        $em->flush();

        // get and build the orders from the client order table
        $this->getOrders($company_id, $date, $end_date);

        $this->output(sprintf("%s: %s db megrendelés lekérdezve", date('H:i:s'), $this->orders['total']['sum']));
        $order->setSummary($this->summary);
        $em->flush();

        // create the order files
        $file_contents = $this->export($date, $end_date);

        if (!empty($file_contents)) {
            $this->output(sprintf("%s: Megrendelés fájl létrehozva", date('H:i:s')));

            $order->setFile($file_contents);
            $order->setSummary($this->summary);
            $em->flush();

            $attachment = $this->getAttachmentName($order);
            // Send the files to kitchen
            $mail_ok = $this->sendMails($date, $end_date, $attachment, $file_contents);
            if ($mail_ok) {
                $this->output(sprintf("%s: Email sikeresen kiküldve", date('H:i:s')));
            }
            else {
                $this->output(sprintf("%s: Email hiba!", date('H:i:s')));
            }

        }

        // update the closing record
        $this->output(sprintf("%s: Befejezve", date('H:i:s')));

        $order->setSummary($this->summary);
        $order->setStatus(DailyOrder::SUCCESS);
        $em->flush();

        return $order;
    }

    public function getAttachmentName(DailyOrder $order)
    {
        if (empty($order->getEndDate())) {
            $attachment = 'Megrendeles_' . $order->getStartDate()->format('Y.m.d.') . $this->ds->getDaysOfWeek($order->getStartDate()->format('N')) . '.xlsx';
        }
        else {
            $attachment = sprintf('Heti_szamla_osszesito_%s.het_%s-%s.xlsx', $order->getStartDate()->format('Y.W'), $order->getStartDate()->format('Y.m.d'), $order->getEndDate()->format('Y.m.d'));
        }

        return $attachment;
    }

    private function getOrders($company_id, \DateTime $date, \DateTime $end_date = null)
    {
        $em = $this->container->get('doctrine')->getManager();
        $ae = $this->container->get('jcs.twig.adminextension');

        // get all the clubs (y)
        $clubs = $em->getRepository('JCSGYKAdminBundle:Club')->getall($company_id);
        // get all the menus (x)
        $menus = $this->ds->getGroup('lunch_types');

        // set the header

        $this->colunms = [
            'name' => 'Intézmények',
            'address' => 'Cím',
        ];
        foreach ($menus as $menu_id => $menu) {
            $this->colunms[$menu_id] = $menu;
        }
        $this->colunms['sum'] = 'Összesen';
        $this->colunms['amount'] = 'Fizetendő';


        // build the club / menu matrix
        foreach ($clubs as $club) {
            $this->orders[$club->getId()] = [
                'name' => $club->getName(),
                'address' => $club->getAddress(),
            ];

            foreach ($menus as $menu_id => $menu) {
                $this->orders[$club->getId()][$menu_id] = 0;
            }
        }

        // get the orders grouped by club and menu
        $orders = $em->getRepository('JCSGYKAdminBundle:ClientOrder')->getDailyOrders($company_id, $date, $end_date);

        // fill the orders matrix
        foreach ($orders as $order) {
            $this->orders[$order['id']][$order['menu']] = $order['orders'];
        }

        $sums = [];
        // calculate the sum fields
        foreach ($this->orders as $club_id => $order) {
            // but skip the info header
            if (is_numeric($club_id)) {
                $club_sum = 0;
                foreach ($menus as $menu_id => $menu) {
                    if (empty($sums[$menu_id])) {
                        $sums[$menu_id] = 0;
                    }
                    $sums[$menu_id] += $order[$menu_id];
                    $club_sum += $order[$menu_id];
                }
                $this->orders[$club_id]['sum'] = $club_sum;
                $this->orders[$club_id]['amount'] = $ae->formatCurrency($this->orders[$club_id]['sum'] * $this->menuCost);
            }
        }

        // add the total sums
        $this->orders['total'] = [
            'name' => 'Összesen',
            'address' => '',
        ];
        $total = 0;
        foreach ($menus as $menu_id => $menu) {
            $this->orders['total'][$menu_id] = $sums[$menu_id];
            $total += $sums[$menu_id];
        }
        $this->orders['total']['sum'] = $total;
        $this->orders['total']['amount'] = $ae->formatCurrency($total * $this->menuCost);
    }

    /**
     * Creates the EcoStat export files in $this->files from the unsent invoices
     */
    public function export(\DateTime $date, \DateTime $end_date = null)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        $data = [
            'et.cim'        => empty($end_date) ? 'MEGRENDELŐ' : 'HETI SZÁMLA ÖSSZESÍTŐ',
            'sp.datum'      => $ae->formatDate(new \DateTime('today')),
            'et.het'        => $date->format('W'),
            'et.datum'      =>  empty($end_date) ?
                    $ae->formatDate($date, 'fd')
                    :
                    sprintf("%s - %s", $ae->formatDate($date, 'fd'), $ae->formatDate($end_date, 'fd')),
            'et.adagszam'   => $this->orders['total']['sum'],
            'et.fizetendo'  => $this->orders['total']['amount'],
            'et.egysegar'   => $ae->formatCurrency($this->menuCost),
            'blocks'    => [
                // column names
                'columns'       => $this->colunms,
                // col keys, except the first two (name and address)
                'cols'          => array_slice(array_keys($this->colunms), 2, count($this->colunms)-3),
                'dailyorders'   => $this->orders,
            ],
        ];

        $template_file = __DIR__.'/../Resources/public/reports/dailyorder.xlsx';

        return $this->container->get('jcs.docx')->make($template_file, $data);
    }

    /**
     * Send the generated files to the kitchen
     *
     * @param \DateTime $date
     * @param string $attachment
     * @param string $attachment_contents
     * @return boolean
     */
    private function sendMails(\DateTime $date, $end_date, $attachment, &$attachment_contents)
    {
        if (!empty($attachment)) {

            $ae = $this->container->get('jcs.twig.adminextension');

            $subject = empty($end_date) ?
                sprintf('Napi kijelentő %s', $ae->formatDate($date, 'fd'))
                :
                sprintf('Heti számla összesítő %s - %s', $ae->formatDate($date, 'fd'), $ae->formatDate($end_date, 'fd'))
            ;

            $mailer_from = 'oszirozsaebed@gmail.com';
            $mailer_from_name = 'JSZSZGYK Szociális étkeztetés';

            $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom([$mailer_from => $mailer_from_name])
//            ->addTo('sodexokonyha@gmail.com')
//            ->addTo('5102.onsite.hu@sodexo.com')
            ->addTo('oszirozsaebed@gmail.com')
            ->addCC('mxbence@gmail.com')
            ->setBody($subject, 'text/plain');

            // add attachment
            $attachment = \Swift_Attachment::newInstance($attachment_contents, $attachment, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $message->attach($attachment);

            $res = $this->container->get('mailer')->send($message);

            return $res;
        }
        else {
            return false;
        }
    }
}

