<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Service for Generating xlsx files from templates
 */
class Xlsx
{
    /** Service container */
    private $container;

    /** phpExcel service */
    private $phpExcelObject;

    /** @var array column map */
    private $columnMap = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

    /** @var array row map for type 401 blocked data*/
    private $rowMap401 = [
        'age_headers'       => 22,
        'age_2'             => 23,
        'age_1'             => 24,
        'comfort_headers'   => 25,
        'comfort'           => 26,
        'income_headers'    => 27,
        'income'            => 28,
        'ownership_headers' => 29,
        'ownership'         => 30,
    ];

    /** @var array row map for type 402 blocked data*/
    private $rowMap402 = [
        'age_headers'       => 18,
        'age_2'             => 19,
        'age_1'             => 20,
        'comfort_headers'   => 21,
        'comfort'           => 22,
        'income_headers'    => 23,
        'income'            => 24,
        'ownership_headers' => 25,
        'ownership'         => 26,
        'eventcount'        => 6,
    ];

    /** @var array row map for type 402 Visit blocked data*/
    private $rowMap402Visit = [
        'age_headers'       => 15,
        'age_2'             => 16,
        'age_1'             => 17,
        'comfort_headers'   => 18,
        'comfort'           => 19,
        'income_headers'    => 20,
        'income'            => 21,
        'ownership_headers' => 22,
        'ownership'         => 23,
        'eventcount'        => 6,
    ];

    /** Constructor */
    public function __construct($container)
    {
        $this->container = $container;
    }


    /**
     * Generate a file from a template, merge the fields, and send the file as a download
     * No data mapping happens
     *
     * @param $template_file
     * @param array $data Array of merge fields
     * @param string $type the type of the stat
     * @param string $homehelp_type help or visit
     * @param null $file_name
     * @return object
     */
    public function make($template_file, $data, $type, $homehelp_type = null, $file_name = null)
    {
        if (empty($template_file)) {
            return false;
        }

        // load excel template
        $this->phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject($template_file);

        $this->phpExcelObject->getProperties()->setCreator("JSZSZGYK Admin")
            ->setLastModifiedBy("Havi Statisztika Szerviz")
            ->setTitle("Statisztika");

        switch ($type) {
            case '401':
                $this->mergeData401($data);
                break;
            case '402':
                if ('help' == $homehelp_type) {
                    $this->mergeData402($data);
                }
                elseif ('visit' == $homehelp_type) {
//                    $this->mergeData402Visit($data);
                }
                break;
        }

        $this->phpExcelObject->getActiveSheet()->setTitle('Statisztika');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->phpExcelObject->setActiveSheetIndex(0);

        // save generated xlsx to database instead of write to file
        $writer = $this->container->get('phpexcel')->createWriter($this->phpExcelObject, 'Excel2007');
        ob_start();
        $writer->save('php://output');

        return ob_get_clean();
    }

    public function makeOrder($template_file, $data)
	{
		function addValue($startValue, $numberToAdd = 1) {
			if (is_numeric($startValue)) {
				return $startValue + $numberToAdd;
			}

			// only incrementing works on letters, adding does not
			$newValue = $startValue;

			if ($numberToAdd < 0) {
				for ($i = 0; $i>$numberToAdd; $i--) {
					$newValue--;
				}
			} else {
				for ($i = 0; $i<$numberToAdd; $i++) {
					$newValue++;
				}
			}

			// now $lastCol = $startCol ! $colNum - 1
			return $newValue;
		}

		// opening template file
		$objPHPExcel =  \PHPExcel_IOFactory::load($template_file);
		$sheet = $objPHPExcel->getActiveSheet();

		$startCol = "A";
		$startRow = 1;	// row number in xlsx where next element can be placed

		// place header in excel file
		$sheet->fromArray($data['header'], $startCol . $startRow);
		unset ($data['header']);

		$startRow +=2;

		// placing elements into excel file
		foreach ($data as $element) {

			$rowNum = count($element);
			$colNum = max(array_map('count', $element));		// max row length (number of columns)

			// placing array into excel file
			$sheet->fromArray($element, '', $startCol . $startRow);


			// Formatting

			// autosize for first column
			$sheet->getColumnDimension($startCol)->setAutoSize(true);

			// given size for every other column
			for ($col = addValue($startCol, 1); $col < addValue($startCol, $colNum); $col++) {
				$sheet->getColumnDimension($col)->setWidth('10pt');
			}

			// thin border around table
			$sheet->getStyle($startCol . $startRow . ":" . addValue($startCol, $colNum - 1) . addValue($startRow, $rowNum - 1))
					->applyFromArray(['borders' => ['outline' => ['style' => \PHPExcel_Style_Border::BORDER_THIN]]]);

			// medium border around orders
			$sheet->getStyle(addValue($startCol, 1) . addValue($startRow, 1) . ":" . addValue($startCol, $colNum - 2) . addValue($startRow, $rowNum - 1))
					->applyFromArray(['borders' => ['outline' => ['style' => \PHPExcel_Style_Border::BORDER_MEDIUM]]]);

			// medium border around days
			$sheet->getStyle(addValue($startCol, 2) . $startRow . ":" . addValue($startCol, $colNum - 2) . $startRow)
					->applyFromArray(['borders' => ['outline' => ['style' => \PHPExcel_Style_Border::BORDER_MEDIUM]]]);

			// dividing sum row with border and making it bold
			if (array_key_exists('sum', $element)) {
				$sheet->getStyle($startCol . addValue($startRow, $rowNum - 1) . ":" . addValue($startCol, $colNum - 1) . addValue($startRow, $rowNum - 1))
						->applyFromArray([
								'borders' => ['top' => ['style' => \PHPExcel_Style_Border::BORDER_THIN]],
								'font' => ['bold' => true]
						]);
			}

			// club name is bold and two cells wide
			$sheet->getStyle($startCol . $startRow)
					->applyFromArray(['font' => ['bold' => true]]);
			$sheet->mergeCells($startCol . $startRow.":".addValue($startCol, 1).$startRow);

			// sum column title is bold
			$sheet->getStyle(addValue($startCol, $colNum - 1) . $startRow)
					->applyFromArray(['font' => ['bold' => true]]);

			// increasing row number so that elements will be placed under each other (+1: gap between elements)
			$startRow += sizeof($element) + 1;
		}

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

		ob_start();
		$objWriter->save('php://output');

		return ob_get_clean();
	}

    /**
     * Merge data into the cells
     * @param array $data
     */
    private function mergeData401($data) {
        $this->phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', $data['ca.cim'] . $data['ca.klub'])
            ->setCellValue('D3', $data['cnum.start'])
            ->setCellValue('D4', $data['cnum.new'])
            ->setCellValue('D5', $data['cnum.all'])
            ->setCellValue('D6', $data['cnum.active'])
            ->setCellValue('D7', $data['cnum.paused'])
            ->setCellValue('D8', $data['cnum.archived'])
            ->setCellValue('D9', $data['cnum.end'])
            ->setCellValue('D11', $data['inv.days'])
            ->setCellValue('D12', $data['inv.discweek'])
            ->setCellValue('E12', $data['inv.discweekcli'])
            ->setCellValue('D13', $data['inv.discend'])
            ->setCellValue('E13', $data['inv.discendcli'])
            ->setCellValue('D14', $data['inv.payweek'])
            ->setCellValue('E14', $data['inv.payweekcli'])
            ->setCellValue('D15', $data['inv.payend'])
            ->setCellValue('E15', $data['inv.payendcli'])
            ->setCellValue('D16', $data['inv.sum'])
            ->setCellValue('D17', $data['inv.acc'])
            ->setCellValue('D18', $data['inv.def'])
            ->setCellValue('D19', $data['cnum.woman'])
            ->setCellValue('D20', $data['cnum.man']);

        // block columns start from 'B'
        $columns = array_values(array_slice($this->columnMap,1));

        foreach ($data['blocks'] as $block => $column) {
            $row = $this->rowMap401[$block];
            $column = array_values($column);

            for ($i = 0; $i < count($column); $i++) {
                $this->phpExcelObject->setActiveSheetIndex(0)->setCellValue($columns[$i] . $row, $column[$i]);
            }
        }
    }

    /**
     * Merge data into the cells
     * @param array $data
     */
    private function mergeData402($data) {
        $this->phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', $data['ca.cim'] . $data['ca.klub'])
            ->setCellValue('D3', $data['cnum.start'])
            ->setCellValue('D4', $data['cnum.new'])
            ->setCellValue('D5', $data['cnum.all'])
            ->setCellValue('D6', $data['cnum.reopened'])
            ->setCellValue('D7', $data['cnum.active'])
            ->setCellValue('D8', $data['cnum.paused'])
            ->setCellValue('D9', $data['cnum.archived'])
            ->setCellValue('D10', $data['cnum.end'])
            ->setCellValue('D12', $data['inv.sum'])
            ->setCellValue('D13', $data['inv.acc'])
            ->setCellValue('D14', $data['inv.def'])
            ->setCellValue('D15', $data['cnum.woman'])
            ->setCellValue('D16', $data['cnum.man'])
            ->setCellValue('I3', $data['inv.hours'])
            ->setCellValue('I4', $data['inv.visits'])
            ->setCellValue('I5', $data['inv.disc'])
            ->setCellValue('I6', $data['inv.pay'])
            ->setCellValue('I7', $data['inv.avg05'])
            ->setCellValue('I8', $data['inv.avg12'])
            ->setCellValue('I9', $data['inv.avg34'])
            ->setCellValue('I10', $data['cnum.inpatient'])
            ->setCellValue('J5', $data['inv.disccli'])
            ->setCellValue('J6', $data['inv.paycli']);

        // block columns start from 'B'
        $columns = array_values(array_slice($this->columnMap,1));

        foreach ($data['blocks'] as $block => $column) {
            // workaround, there is no eventcount block, just reference
            if ('eventcount' != $block) {
                $row = $this->rowMap402[$block];
                $column = array_values($column);

                for ($i = 0; $i < count($column); $i++) {
                    $this->phpExcelObject->setActiveSheetIndex(0)->setCellValue($columns[$i] . $row, $column[$i]);
                }
            }
        }
    }

    /**
     * Merge data into the cells
     * @param array $data
     */
    private function mergeData402Visit($data) {
        $this->phpExcelObject->setActiveSheetIndex(0)
            ->setCellValue('A1', $data['ca.cim'] . $data['ca.klub'])
            ->setCellValue('D3', $data['cnum.start'])
            ->setCellValue('D4', $data['cnum.new'])
            ->setCellValue('D5', $data['cnum.reopened'])
            ->setCellValue('D6', $data['cnum.all'])
            ->setCellValue('D7', $data['cnum.active'])
            ->setCellValue('D8', $data['cnum.paused'])
            ->setCellValue('D9', $data['cnum.archived'])
            ->setCellValue('D10', $data['cnum.end'])
            ->setCellValue('D12', $data['cnum.woman'])
            ->setCellValue('D13', $data['cnum.man'])
            ->setCellValue('I3', $data['inv.visits']);

        foreach ($data['blocks'] as $block => $column) {
            $row = $this->rowMap402Visit[$block];
            $column = array_values($column);

            if ('eventcount' == $block) {
                // block columns start from 'F'
                $columns = array_values(array_slice($this->columnMap, 5));
            } else {
                // block columns start from 'B'
                $columns = array_values(array_slice($this->columnMap, 1));
            }

            for ($i=0; $i < count($column); $i++) {
                $this->phpExcelObject->setActiveSheetIndex(0)->setCellValue($columns[$i].$row, $column[$i]);
            }
        }
    }
}