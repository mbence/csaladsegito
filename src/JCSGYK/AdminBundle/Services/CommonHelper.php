<?php
/**
 * This file contains common functions that can be used anywhere in the project
 *
 * @author Soma Veszelovszki <soma.veszelovszki@gmail.com>
 * @since 2016-01-21
 */

namespace JCSGYK\AdminBundle\Services;

class CommonHelper {

	/**
	 * Adds value to number or string
	 * e.g. when we need to switch columns of an Excel table marked by letters
	 * @param string|int $startValue
	 * @param int $numberToAdd
	 * @return int|string
	 */
	public static function addValue($startValue, $numberToAdd = 1) {
		if (is_numeric($startValue)) {
			return $startValue + $numberToAdd;
		}

		// only incrementing works on letters, adding does not
		$newValue = $startValue;

		if ($numberToAdd < 0) {
			for ($i = 0; $i > $numberToAdd; $i--) {
				$newValue--;
			}
		} else {
			for ($i = 0; $i < $numberToAdd; $i++) {
				$newValue++;
			}
		}

		// now $lastCol = $startCol ! $colNum - 1
		return $newValue;
	}

}