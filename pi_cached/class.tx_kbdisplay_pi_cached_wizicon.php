<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Bernhard Kraft <kraftb@think-open.at>
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
 * Class that adds the wizard icon.
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbdisplay
 */
class tx_kbdisplay_pi_cached_wizicon {

					/**
					 * Processing the wizard items array
					 *
					 * @param	array		$wizardItems: The wizard items
					 * @return	array		Modified array with wizard items
					 */
					function proc($wizardItems) {
						global $LANG;

						$wizardItems['plugins_tx_kbdisplay_pi_cached'] = array(
							'icon' => t3lib_extMgm::extRelPath('kb_display').'pi_cached/ce_wiz.gif',
							'title' => $LANG->sL('LLL:EXT:kb_display/locallang.xml:pi_cached_title'),
							'description' => $LANG->sL('LLL:EXT:kb_display/locallang.xml:pi_cached_plus_wiz_description'),
							'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=kb_display_pi_cached',
						);

						return $wizardItems;
					}

				}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/pi_cached/class.tx_kbdisplay_pi_cached_wizicon.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/pi_cached/class.tx_kbdisplay_pi_cached_wizicon.php']);
}

?>
