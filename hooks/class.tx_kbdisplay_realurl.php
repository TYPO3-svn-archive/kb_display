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
 * Hook method for realurl extension
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


class tx_kbdisplay_realurl {

	function override_cHash(&$params, &$parentObj) {
		$cHash_set = false;

		if (!(is_array($_POST) && count($_POST))) {
			$realURLconfig = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'];
			$filterConfig = $realURLconfig['postVarSets']['_DEFAULT']['filter'];
			$tempGET = $localGET = $_GET;
			unset($localGET['cHash']);
			if (is_array($filterConfig) && count($filterConfig)) {
				$cHash_set = true;
				foreach ($filterConfig as $fConf) {
					$getVar = $fConf['GETvar'];
					if (preg_match('/tx_kbdisplay_pi_cached\[filter\]\[(.*)\]/', $getVar, $matches)>0) {
						$filterKey = $matches[1];
						unset($localGET['tx_kbdisplay_pi_cached']['filter'][$filterKey]);
						if (is_array($localGET['tx_kbdisplay_pi_cached']['filter']) && !count($localGET['tx_kbdisplay_pi_cached']['filter'])) {
							unset($localGET['tx_kbdisplay_pi_cached']['filter']);
							if (!count($localGET['tx_kbdisplay_pi_cached'])) {
								unset($localGET['tx_kbdisplay_pi_cached']);
							}
						}
					} else {
						$cHash_set = false;
						break;
					}
				}
				if ($cHash_set) {
					if (is_array($localGET) && !count($localGET) && is_array($tempGET) && count($tempGET)) {
						// Only valid values have been in the GET vars. Force cHash
						$cHash_array = t3lib_div::cHashParams(t3lib_div::implodeArrayForUrl('', $tempGET));
						$cHash_calc = t3lib_div::shortMD5(serialize($cHash_array));
						$GLOBALS['TSFE']->cHash = $_GET['cHash'] = $cHash_calc;
					}
				}
			}
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_realurl.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_realurl.php']);
}

?>
