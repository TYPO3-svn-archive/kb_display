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


require_once(PATH_tslib.'interfaces/interface.tslib_content_getdatahook.php');

class tx_kbdisplay_getData implements tslib_content_getDataHook {

	/**
	 * Extends the getData()-Method of tslib_cObj to process more/other commands
	 *
	 * @param	string		full content of getData-request e.g. "TSFE:id // field:title // field:uid"
	 * @param	array		current field-array
	 * @param	string		currently examined section value of the getData request e.g. "field:title"
	 * @param	string		current returnValue that was processed so far by getData
	 * @param	tslib_cObj	parent content object
	 * @return	string		get data result
	 */
	public function getDataExtension($getDataString, array $fields, $sectionValue, $returnValue, tslib_cObj &$parentObject) {
		$parts = explode(':', $sectionValue, 2);
		$key = trim($parts[1]);
		if ((string)$key!='') {
			switch(strtolower(trim($parts[0]))) {
				case 'literal':
				case '//literal':
					$returnValue = '{'.chr(10).$parts[1].chr(10).'}';
				break;
				case 'field':
					$returnValue = $parentObject->getGlobal($key, $fields);
				break;
				case 'getvar':
					list($firstKey, $rest) = explode('|', $key, 2);
					if (strlen(trim($firstKey))) {
						$returnValue = t3lib_div::_GET(trim($firstKey));
							// Look for deeper levels:
						if (strlen(trim($rest))) {
							$returnValue = is_array($returnValue) ? $parentObject->getGlobal($rest, $returnValue) : '';
						}
							// Check that output is not an array:
						if (is_array($returnValue)) {
							$returnValue = '';
						}
					}
				break;
				case 'postvar':
					list($firstKey, $rest) = explode('|', $key, 2);
					if (strlen(trim($firstKey))) {
						$returnValue = t3lib_div::_POST(trim($firstKey));
							// Look for deeper levels:
						if (strlen(trim($rest))) {
							$returnValue = is_array($returnValue) ? $parentObject->getGlobal($rest, $returnValue) : '';
						}
							// Check that output is not an array:
						if (is_array($returnValue)) {
							$returnValue = '';
						}
					}
				break;
			}
		}
		return $returnValue;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_getData.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_getData.php']);
}

?>
