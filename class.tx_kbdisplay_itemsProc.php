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

require_once(PATH_kb_display.'lib/class.tx_kbdisplay_flexFields.php');


/**
 * Class for adding all comparable fields of the tables set to
 * the list of available fields
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	kb_display
 */
class tx_kbdisplay_itemsProc extends tx_kbdisplay_flexFields {
	private $tables = array();
// TODO: Make it possible to move additional tables up/down in flexform
// For this it is required to add an "alias" field to each of the flexform
// section elements, which contains an unique identifier for each element
// when elements get moved this identifier stays the same, and allows it
// to create the query according to it.


	public function compareFields(&$params, &$parentObj) {
		// TODO: Caching / Cache
		$flexXML = $params['row']['pi_flexform'];
		if ($flexXML) {
			$flexData = t3lib_div::xml2array($flexXML);
			if (is_array($flexData)) {
				$tables[] = $flexData['data']['sDEF']['lDEF']['field_table']['vDEF'];
				$additionalTables = $flexData['data']['sheet_tables']['lDEF']['list_tables']['el'];
				$extraTables = $this->parseSectionElements($additionalTables);
				foreach ($extraTables as $extraTable) {
					$tables[] = $extraTable['field_table'];
				}
				//print_r($params);
				//exit();
				$cnt = 0;
				$items = $params['items'];
				foreach ($tables as $table) {
					$this->setItems($items, $table, $cnt);
					$cnt++;
				}
				$params['items'] = $items;
			}
		}
	}

	public function sortFields(&$params, &$parentObj) {
		// TODO: Caching / Cache
		$flexXML = $params['row']['pi_flexform'];
		if ($flexXML) {
			$flexData = t3lib_div::xml2array($flexXML);
			if (is_array($flexData)) {
				$tables[] = $flexData['data']['sDEF']['lDEF']['field_table']['vDEF'];
				$additionalTables = $flexData['data']['sheet_tables']['lDEF']['list_tables']['el'];
				$extraTables = $this->parseSectionElements($additionalTables);
				foreach ($extraTables as $extraTable) {
					$tables[] = $extraTable['field_table'];
				}
				$cnt = 0;
				$items = $params['items'];
				foreach ($tables as $table) {
					$this->setItems($items, $table, $cnt);
					$cnt++;
				}
				$params['items'] = $items;
			}
		}
	}

	function setItems(&$items, $table, $cnt) {
		$fields = $this->getAllFields($table);
		$LL = $GLOBALS['TCA'][$table]['ctrl']['title'];
		$tableLabel = $GLOBALS['LANG']->sL($LL);
		$fieldCnt = sprintf('%03d', $cnt);
		foreach ($fields as $idx => $field) {
			$LL = $GLOBALS['TCA'][$table]['columns'][$field]['label'];
			if (!$LL) {
					// TODO #5432: Translate this labels to localized pedants using LLXML
					// Bugtracker: http://forge.typo3.org/issues/show/5432
				switch ($field) {
					case 'uid':
						$fieldLabel = 'UID';
					break;
					case 'pid':
						$fieldLabel = 'PID';
					break;
					case 'deleted':
						$fieldLabel = 'Deleted';
					break;
					case 'crdate':
						$fieldLabel = 'Creation date';
					break;
					default:
					case 'tstamp':
						$fieldLabel = 'Last modification';
					break;
					case $GLOBALS['TCA'][$table]['ctrl']['sortby']:
						$fieldLabel = 'Sorting';
					break;
					default:
						$fieldLabel = '['.$field.']';
					break;
				}
			} else {
				$fieldLabel = $GLOBALS['LANG']->sL($LL);
				if (substr($fieldLabel, -1)===':') {
					$fieldLabel = substr($fieldLabel, 0, -1);
				}
			}
			$label = $tableLabel.' ('.($cnt+1).'): '.$fieldLabel;
			$items[] = array(
				$label,
				$field.'__'.$fieldCnt,
			);
		}
	}

	/**
	 * Get all fields for the current table
	 *
	 * @return	void
	 */
	private function getAllFields($table) {
		$sqlFields = $GLOBALS['TYPO3_DB']->admin_get_fields($table);
		t3lib_div::loadTCA($table);
		if (!is_array($GLOBALS['TCA'][$table])) {
			return array();
		}
		$fields = array_keys($GLOBALS['TCA'][$table]['columns']);
		$fields[] = 'uid';
		$fields[] = 'pid';
		$fields[] = 'deleted';
		$fields[] = 'crdate';
		$fields[] = 'tstamp';
		if (strlen($GLOBALS['TCA'][$table]['ctrl']['sortby']) && !in_array($GLOBALS['TCA'][$table]['ctrl']['sortby'], $fields)) {
			$fields[] = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
		}
		$sqlKeys = array_keys($sqlFields);
		$sqlKeys = array_diff($sqlKeys, $fields);
		$fields = array_merge($fields, $sqlKeys);
		return $fields;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/class.tx_kbdisplay_itemsProc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/class.tx_kbdisplay_itemsProc.php']);
}

?>
