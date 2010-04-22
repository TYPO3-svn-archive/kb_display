<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008-2010 Bernhard Kraft (kraftb@think-open.at)
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
 * XCLASS for t3lib_tceforms
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

class ux_t3lib_TCEforms extends t3lib_TCEforms {

	/**
	 * Returns true, if the evaluation of the required-field code is OK.
	 *
	 * @param	string		The required-field code
	 * @param	array		The record to evaluate
	 * @param	string		FlexForm value key, eg. vDEF
	 * @return	boolean
	 */
	function isDisplayCondition($displayCond,$row,$ffValueKey='') {
		$OR_parts = t3lib_div::trimExplode('||', $displayCond, 1);
		$res = false;
		foreach ($OR_parts as $OR_part) {
			$AND_parts = t3lib_div::trimExplode('&&', $OR_part, 1);
			$AND_res = true;
			foreach ($AND_parts as $AND_part) {
				$AND_res &= $this->isDisplayCondition_eval($AND_part, $row, $ffValueKey);
			}
			$res |= $AND_res;
		}
		return $res;
	}


	/**
	 * Returns true, if the evaluation of the required-field code is OK.
	 *
	 * @param	string		The required-field code
	 * @param	array		The record to evaluate
	 * @param	string		FlexForm value key, eg. vDEF
	 * @return	boolean
	 */
	function isDisplayCondition_eval($displayCond,$row,$ffValueKey='')	{
		$output = FALSE;

		$parts = explode(':',$displayCond);
		switch((string)$parts[0])	{	// Type of condition:
			case 'FIELD':
				list($fieldName, $from, $length) = explode(',', $parts[1]);
				$from = intval($from);
				$length = intval($length);
				$theFieldValue = $ffValueKey ? $row[$fieldName][$ffValueKey] : $row[$fieldName];
// echo $theFieldValue."<br >/\n";
				if ($from || $length) {
					$theFieldValue = substr($theFieldValue, $from, $length);
				}

				switch((string)$parts[2])	{
					case 'REQ':
						if (strtolower($parts[3])=='true')	{
							$output = $theFieldValue ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !$theFieldValue ? TRUE : FALSE;
						}
					break;
					case '>':
						$output = $theFieldValue > $parts[3];
					break;
					case '<':
						$output = $theFieldValue < $parts[3];
					break;
					case '>=':
						$output = $theFieldValue >= $parts[3];
					break;
					case '<=':
						$output = $theFieldValue <= $parts[3];
					break;
					case '-':
					case '!-':
						$cmpParts = explode('-',$parts[3]);
						$output = $theFieldValue >= $cmpParts[0] && $theFieldValue <= $cmpParts[1];
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case 'IN':
					case '!IN':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case '=':
					case '!=':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
				}
			break;
			case 'EXT':
				switch((string)$parts[2])	{
					case 'LOADED':
						if (strtolower($parts[3])=='true')	{
							$output = t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'REC':
				switch((string)$parts[1])	{
					case 'NEW':
						if (strtolower($parts[2])=='true')	{
							$output = !(intval($row['uid']) > 0) ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = (intval($row['uid']) > 0) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'HIDE_L10N_SIBLINGS':
				if ($ffValueKey==='vDEF')	{
					$output = TRUE;
				} elseif ($parts[1]==='except_admin' && $GLOBALS['BE_USER']->isAdmin())	{
					$output = TRUE;
				}
			break;
			case 'HIDE_FOR_NON_ADMINS':
				$output = $GLOBALS['BE_USER']->isAdmin() ? TRUE : FALSE;
			break;
			case 'VERSION':
				switch((string)$parts[1])	{
					case 'IS':
						if (strtolower($parts[2])=='true')	{
							$output = intval($row['pid'])==-1 ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = !(intval($row['pid'])==-1) ? TRUE : FALSE;
						}
					break;
				}
			break;
		}

		return $output;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/xclass/class.ux_t3lib_tceforms.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/xclass/class.ux_t3lib_tceforms.php']);
}

?>
