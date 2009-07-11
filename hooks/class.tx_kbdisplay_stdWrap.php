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


require_once(PATH_tslib.'interfaces/interface.tslib_content_stdwraphook.php');


class tx_kbdisplay_stdWrap implements tslib_content_stdWrapHook {

	/**
	 * Hook for modifying $content before core's stdWrap does anything
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript stdWrap properties
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapPreProcess($content, array $configuration, tslib_cObj &$parentObject) {
		return $content;
	}

	/**
	 * Hook for modifying $content after core's stdWrap has processed setContentToCurrent, setCurrent, lang, data, field, current, cObject, numRows, filelist and/or preUserFunc
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript stdWrap properties
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapOverride($content, array $configuration, tslib_cObj &$parentObject) {
		if ($configuration['field']) {
			$content = $this->getFieldVal($configuration['field'], $parentObject);
		}
		return $content;
	}

	/**
	 * Returns the value for the field from $this->data. If "//" is found in the $field value that token will split the field values apart and the first field having a non-blank value will be returned.
	 *
	 * @param	string		The fieldname, eg. "title" or "navtitle // title" (in the latter case the value of $this->data[navtitle] is returned if not blank, otherwise $this->data[title] will be)
	 * @return	string
	 */
	function getFieldVal($field, &$parentObject) {
		if (!strstr($field, '//')) {
			if (!strstr($field, '|')) {
				return $parentObject->data[trim($field)];
			} else {
				return $parentObject->getGlobal(trim($field), $parentObject->data);
			}
		} else {
			$sections = t3lib_div::trimExplode('//', $field, 1);
			while (list(,$k)=each($sections)) {
				if (strcmp($parentObject->data[$k], '')) {
					return $parentObject->data[$k];
				}
			}
		}
	}


	/**
	 * Hook for modifying $content after core's stdWrap has processed override, preIfEmptyListNum, ifEmpty, ifBlank, listNum, trim and/or more (nested) stdWraps
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript "stdWrap properties".
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapProcess($content, array $configuration, tslib_cObj &$parentObject) {
		if ($configuration['sprintf'] || $configuration['sprintf.']) {
			if ($configuration['sprintf.']) {
				$format = $parentObject->stdWrap($configuration['sprintf'], $configuration['sprintf.']);
			} else {
				$format = $configuration['sprintf'];
			}
			$content = sprintf($format, $content);
		}
		if ($configuration['validEmail'] || $configuration['validEmail.']) {
			$email = $parentObject->stdWrap($configuration['validEmail'], $configuration['validEmail.']);
			if (!t3lib_div::validEmail($email)) {
				$content = '';
			}
		}
		return $content;
	}


	/**
	 * Hook for modifying $content after core's stdWrap has processed anything but debug
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript stdWrap properties
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapPostProcess($content, array $configuration, tslib_cObj &$parentObject) {
		if ($config = $configuration['pregReplace.']) {
			$pattern = $parentObject->stdWrap($configuration['pregReplace.']['pattern'], $configuration['pregReplace.']['pattern.']);
			$replacement = $parentObject->stdWrap($configuration['pregReplace.']['replacement'], $configuration['pregReplace.']['replacement.']);
			if ($pattern) {
				$content = preg_replace($pattern, $replacement, $content);
			}
		}
		if ($register = $configuration['appendToRegister']) {
			$GLOBALS['TSFE']->register[$register] .= $content;
		}
		if ($register = $configuration['setRegister']) {
			$GLOBALS['TSFE']->register[$register] = $content;
		}
		return $content;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_stdWrap.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_stdWrap.php']);
}

?>
