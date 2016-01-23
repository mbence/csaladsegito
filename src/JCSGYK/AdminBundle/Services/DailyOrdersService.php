<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\Config\Definition\Exception\Exception;
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
    private $columns = [];

	/** Menu types combined with delivery types */
	private $rows = [];

	/** Menu types without delivery types */
    private $deliveryRows = [];

    /** Data array of clubs  */
    private $clubs = [];

    /** Data array of the daily orders */
    private $orders = [];

    /** Data array of the daily order totalss */
    private $totals = [];

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
        $this->columns = [];
        $this->rows = [];
		$this->deliveryRows = [];
        $this->clubs = [];
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

        //$this->output(sprintf("%s: %s db megrendelés lekérdezve", date('H:i:s'), $this->orders['total']['sum']));
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

        // get all the menus (x)
        //$menus = $this->ds->getGroup('lunch_types');
        //$deliveryTypes = $this->ds->getGroup('delivery');

        // menus combined with deliveries
        $this->rows = [
                'normal-badell'=>'normál badellás',
                'normal-pack'=>'normál csomagolt',
                'dietary-badell'=>'diétás badellás',
                'dietary-pack'=>'diétás csomagolt',
                'stomach/gall-badell'=>'gyomros-epés badellás',
                'stomach/gall-pack'=>'gyomros-epés csomagolt'
        ];

        // delivery block - menus
        $this->deliveryRows = [
                'normal'=>'normál',
                'dietary'=>'diétás',
                'stomach/gall'=>'gyomros-epés',
				'sum'=>'Összesen'
        ];

        // set instituions - they contain their own order array
        foreach ($em->getRepository('JCSGYKAdminBundle:Club')->getall($company_id) as $club) {   // gets all the clubs (y)
            // club object will contain all orders of the club
            $this->clubs[$club->getId()] = [
                    'name' => $club->getName(),
                    'address' => $club->getAddress(),
                    'orders' => []
            ];
        }

        // we need the sums of the orders as well
        $this->clubs['sum'] = [
                'name' => 'Összesen klubok',
                'address' => '',
                'orders' => []
        ];
        $this->clubs['deliveries'] = [
                'name' => 'Házhozszállítás',
                'address' => '',
                'orders' => []
        ];

        // initialize the order array for all clubs
        foreach ($this->clubs as $index=>$club) {
			// creates array for every day
			for ($day = 1; $day <=7; $day++) {
				$this->clubs[$index]['orders'][$day] = [];
			}
			// ... and sum of days
			$this->clubs[$index]['orders']['weekly_sum'] = [];


			// the delivery block contains different rows (menus without delivery type)
			$rows = ('deliveries' !== $index) ? $this->rows : $this->deliveryRows;


			// every array (days (1-7) and sum) contains orders for all menu types
			foreach ($this->clubs[$index]['orders'] as $day=>$element) {
				foreach ($rows as $menuType => $name) {
					// initializes orders of all menu types
					$this->clubs[$index]['orders'][$day][$menuType] = 0;
				}

				if ('deliveries' === $index) {
					$this->clubs['deliveries']['orders'][$day][''] = 0;
				}
			}

		}

        try {
            // get the orders grouped by club and menu
            $orders = $em->getRepository('JCSGYKAdminBundle:ClientOrder')->getDailyOrders($company_id, $date, $end_date);
        } catch (Exception $e) {
			echo "Error: " . $e->getMessage() . 'Process terminated.';
			die;
		}



		// filling empty days of orders table with empty arrays
		for ($day = 1; $day <=7; $day++) {
			$orders[$day] = !empty($orders[$day]) ? $orders[$day] : [];
		}

        $weekdays_sum = 0;
        $weekend_sum = 0;

		foreach ($orders as $day_of_week=>$day) {

			if (empty($day)) {
				break;
			}
			// fill the orders matrix
			foreach ($day as $order) {
				//$this->orders[$order['id']][$order['menu']] += $order['orders'];
				$menu = '';         // name of menu
				$delivery = '';     // name of delivery type

				/*
				Menu:
					[495] => Normál A
					[496] => Normál B
					[497] => Diétás
					[498] => Gyomor
					[499] => Epe
				*/
				switch($order['menu']) {
					case 495:       // Normál A
					case 496:       // Normál B
						$menu = 'normal';
						break;
					case 497:       // Diétás
						$menu = 'dietary';
						break;
					case 498:       // Gyomor
					case 499:       // Epe
						$menu = 'stomach/gall';
						break;
					default:    // throw an Exception?
						break;
				}

				/*
				Delivery:
					[593] => Helyben fogyasztás (0 Ft)
					[594] => Elvitel (81 Ft)
					[595] => Kiszállítás (81+64 Ft)
					[596] => Kedvezményes kiszállítás (0 Ft)
				    [597] => Közös kiszállítás (81 Ft)
				*/
				switch($order['delivery']) {
					case 593:       // Helyben fogyasztás
						$delivery = 'badell';
						break;
					case 594:       // Elvitel
					case 595:       // Kiszállítás
					case 596:       // Kedvezményes kiszállítás
                    case 597:       // Közös kiszállítás
						$delivery = 'pack';
						break;
					default:    // throw an Exception?
						break;
				}

				// set number of orders in club object
				if (!empty($menu) && !empty($delivery)) {

					// format for menu and delivery types
					// e.g. 'normal-badell'
					$menuDelType = $menu.'-'.$delivery;

					$this->clubs[$order['id']]['orders'][$day_of_week][$menuDelType] += $order['orders'];

					// add to sum of given menu (weekly club sum)
					$this->clubs[$order['id']]['orders']['weekly_sum'][$menuDelType] += $order['orders'];

					// add to total daily sum of menu
					$this->clubs['sum']['orders'][$day_of_week][$menuDelType] += $order['orders'];

					// add to total weekly sum of menu
					$this->clubs['sum']['orders']['weekly_sum'][$menuDelType] += $order['orders'];
				}

				// increase the weekend / weekday counts
				if (!empty($order['weekend'])) {
					$weekend_sum += $order['orders'];
				}
				else {
					$weekdays_sum += $order['orders'];
				}

				// increase the delivery count
				if ($delivery === 'pack') {
					// given menu given day
					$this->clubs['deliveries']['orders'][$day_of_week][$menu] += $order['orders'];

					// dailys sum of all menus
					$this->clubs['deliveries']['orders'][$day_of_week]['sum'] += $order['orders'];

					//weekly sum of given menu
					$this->clubs['deliveries']['orders']['weekly_sum'][$menu] += $order['orders'];

					//weekly sum of all menus (all deliveries)
					$this->clubs['deliveries']['orders']['weekly_sum']['sum'] += $order['orders'];
				}
			}
		}

        /*

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

        $this->totals['weekdays']['sum'] = $weekdays_sum;
        $this->totals['weekdays']['amount'] = $ae->formatCurrency($weekdays_sum * $this->menuCost);

        $this->totals['weekend']['sum'] = $weekend_sum;
        $this->totals['weekend']['amount'] = $ae->formatCurrency($weekend_sum * $this->menuCost);

        */
    }

    /**
     * Creates xlsx file of orders and returns with it
     * @param \DateTime $date
     * @param \DateTime|null $end_date
     * @return null
     */
    public function export(\DateTime $date, \DateTime $end_date = null)
    {
        $ae = $this->container->get('jcs.twig.adminextension');
        /*$data = [
            'et.cim'           => empty($end_date) ? 'MEGRENDELŐ' : 'HETI SZÁMLA ÖSSZESÍTŐ',
            'sp.datum'         => $ae->formatDate(new \DateTime('today')),
            'et.het'           => $date->format('W'),
            'et.datum'         => empty($end_date) ?
                    $ae->formatDate($date, 'fd') :
                    sprintf("%s - %s", $ae->formatDate($date, 'fd'), $ae->formatDate($end_date, 'fd')),
            'et.adagszam_hp'   => $this->totals['weekdays']['sum'],
            'et.fizetendo_hp'  => $this->totals['weekdays']['amount'],
            'et.adagszam_szv'  => $this->totals['weekend']['sum'],
            'et.fizetendo_szv' => $this->totals['weekend']['amount'],
            'et.adagszam'      => $this->orders['total']['sum'],
            'et.fizetendo'     => $this->orders['total']['amount'],
            'et.egysegar'      => $ae->formatCurrency($this->menuCost),
            'blocks'           => [
                // column names
                'columns'     => $this->columns,
                // col keys, except the first two (name and address)
                'cols'        => array_slice(array_keys($this->columns), 2, count($this->columns) - 3),
                'dailyorders' => $this->orders,
            ]
        ];

        //$template_file = __DIR__.'/../Resources/public/reports/dailyorder.xlsx';

        return $this->container->get('jcs.docx')->make($template_file, $data);
        */

		if (empty($this->clubs)) {
			return null;
		}

		// set the header
		$columns = [
				'row_name' => '',
		];

		// add days of week as columns
		for($day = 1; $day <=7; $day++) {
			$columns[$day] = $this->ds->getDaysOfWeek($day);
		}

		// sum column contains sum of orders of given menu (with given delivery) of given club (whole week)
		// For this to actually show something, first it's needed to decide which days to show in table
		// (only given day, or every day before it as well)
		$columns['weekly_sum'] = 'Összesen';

		$data = [];

		$end_date = !empty($end_date) ? $end_date : $date;

		$start_day = $date->format('N');
		$end_day = $end_date->format('N');

		if (1 == $start_day && 7 == $end_day) {
			$data['header'] = [
					'date' => $date->format('Y. m. d.') . ' - ' . $end_date->format('Y. m. d.'),
					'title' => 'HETI SZÁMLA ÖSSZESÍTŐ',
					'gap1' => '',
					'gap2' => '',
					'sendDate' => 'Elküldve: ' . date('Y. m. d.')
			];
		} else {
			$data['header'] = [
					'date' => $date->format('Y. m. d.') . ' (' . $this->ds->getDaysOfWeek($date->format('N')) . ')',
					'title' => 'MEGRENDELŐ',
					'gap1' => '',
					'gap2' => '',
					'sendDate' => 'Elküldve: ' . date('Y. m. d.')
			];
		}



		// generating 2-dimensional arrays from each club that can be placed into xlsx sheet
		foreach ($this->clubs as $index=>$club) {

			// rows:	1.	name of club and column titles
			// 			2-	all the menus and orders
			// the delivery block contains different rows (menus without deliveries)
			$rows = array_merge(['column_name'=>'col_name'], ('deliveries' !== $index) ? $this->rows : $this->deliveryRows);

			$clubArray = [];

			foreach($rows as $row=>$row_title) {

				// adds columns (name, days, sum)
				foreach($columns as $col=>$col_title) {
					// sets every element of the 2 dimensional array

					//$clubArray[$row][$col] = ('column_name' !== $row && is_numeric($col)) ? $club['orders'][$col][$row] : $col_title;

					// sets every element of the 2 dimensional array
					if ('column_name' === $row) {
						// first row contains column names
						$clubArray[$row][$col] = $col_title;
					} else if (is_numeric($col)) {
						// for day columns (Monday-Sunday) column names are numeric values (1-7)
						// --> if we are not in the first row (which key is 'column_name'), we set element to the number of orders
						// (if we are in a column where we need to display the number of orders)
						$clubArray[$row][$col] = ($start_day <= $col && $col <= $end_day) ?
								(!empty($club['orders'][$col][$row]) ? $club['orders'][$col][$row] : '0') : 0;
					} else if ('weekly_sum' === $col) {
						// weekly sum only needed when order request was made for whole week
						if (1 == $start_day && 7 == $end_day) {
							$clubArray[$row]['weekly_sum'] = !empty($club['orders']['weekly_sum'][$row]) ?
									$club['orders']['weekly_sum'][$row] : '0';
						}
					} else {
						// every other cells should be empty
						$clubArray[$row][$col] = '';
					}
				}

				// sets title of row (already added as part of column array, but empty string)
				$clubArray[$row]['row_name'] = $row_title;

				// next to every menu name an 'E' is required in the second cell of the xlsx that mark lunch ('Ebéd')
				array_splice($clubArray[$row], 1, 0, ['mealType'=>('column_name' !== $row) ? 'E' : '']);
			}

			// first element of name column is name and address of club
			$clubArray['column_name']['row_name'] = $club['name'] . (!empty($club['address']) ? ' - ' . $club['address'] : '');

			$data[] = $clubArray;
		}

		// dailyorder_test.xlsx is an empty xlsx file
		$template_file = __DIR__.'/../Resources/public/reports/dailyorder_test.xlsx';

		return $this->container->get('jcs.xlsx')->makeOrder($template_file, $data);
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
            ->addTo('elelmezes@jszszgyk.hu')
            ->addCC('oszirozsaebed@gmail.com')
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

