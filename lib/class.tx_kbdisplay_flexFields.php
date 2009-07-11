<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Bernhard Kraft <kraftb@think-open.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Base Class for parsing flexForm XML data arrays
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbt3tris
 */
class tx_kbdisplay_flexFields {


	/**
	 * Parses an FlexForm "section" into an array
	 *
	 * @param	array		The FlexForm XML array containing the section elments
	 * @param	string		The vDEF language key to use
	 * @return	array		An array containing a cleaned up version of all elements of the FlexForm section
	 */
	protected function parseSectionElements($sectionElements, $vDEF = 'vDEF') {
		$result = array();
		if (is_array($sectionElements) && count($sectionElements)) {
			foreach ($sectionElements as $sectionElement) {
				$sectionElement = array_shift($sectionElement);
				$sectionElement = array_shift($sectionElement);
				$dataArray = $this->parseSectionElements_fields($sectionElement, $vDEF);
				if (is_array($dataArray)) {
					$result[] = $dataArray;
				}
			}
		}
		return $result;
	}


	/**
	 * Parses the fields of a FlexForm into an array
	 *
	 * @param	array		The FlexForm XML array containing the section elments
	 * @param	string		The vDEF language key to use
	 * @return	array		An array containing a cleaned up version of all elements of the FlexForm section
	 */
	protected function parseSectionElements_fields($sectionElement, $vDEF = 'vDEF') {
		$result = array();
		foreach ($sectionElement as $field => $value) {
			if ($value['el']) {
				$result[$field] = $this->parseSectionElements($value['el'], $vDEF);
			} else {
				$result[$field] = $value[$vDEF];
			}
		}
		return $result;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_flexFields.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_flexFields.php']);
}

?>
