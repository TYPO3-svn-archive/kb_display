<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008-2009 Bernhard Kraft (kraftb@kraftb.at)
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
 * Hook method for t3lib_befunc
 *
 * @author	Bernhard Kraft <kraftb@kraftb.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

require_once(PATH_kb_display.'lib/class.tx_kbdisplay_flexFields.php');

class tx_kbdisplay_t3libbefunc extends tx_kbdisplay_flexFields {
	var $currentRecursion = false;
	var $maxRecursion = array(
		'EXT:kb_display/res/flexform_ds_pi_cached__subcriteria_tpl.xml' => 2,
		'default' => -1,
	);

	public function getFlexFormDS_postProcessDS(&$dataStructArray, $conf, $row, $table, $fieldName) {
		if ($conf) {
			$this->currentRecursion = $this->maxRecursion;
		}
		if (is_array($dataStructArray)) {
			$dataStructArray = $this->replaceIncludes_recursive($dataStructArray);
		}
		if ($conf) {
	    if (is_array($dataStructArray) && is_array($dataStructArray['sheets']['sheet_tables']['ROOT']['el']['list_tables']['el']['item_table']['el']['list_criteria_section']['el']['list_criteria_item']['el'])) {
				$this->setCriteriaFields($table, $row, $dataStructArray['sheets']['sheet_tables']['ROOT']['el']['list_tables']['el']['item_table']['el']['list_criteria_section']['el']['list_criteria_item']['el']);
			}
		}
/*
		if ($conf) {
			print_r($dataStructArray);
			exit();
		}
*/
	}

	private function setCriteriaFields($table, $row, &$fieldCriteriaConfig) {
		$flexXML = $row['pi_flexform'];
		$tables = array();
		if ($flexXML) {
			$flexData = t3lib_div::xml2array($flexXML);
			if (is_array($flexData)) {
				$tables[] = $flexData['data']['sDEF']['lDEF']['field_table']['vDEF'];
				$additionalTables = $flexData['data']['sheet_tables']['lDEF']['list_tables']['el'];
				$extraTables = $this->parseSectionElements($additionalTables);
				foreach ($extraTables as $extraTable) {
					$tables[] = $extraTable['field_table'];
				}
			}
		}
		foreach ($tables as $curTable) {
			$this->setCriteriaFields_table($curTable, $fieldCriteriaConfig);
		}
	}

	private function setCriteriaFields_table($table, &$fieldCriteriaConfig) {
		t3lib_div::loadTCA($table);
		if (is_array($GLOBALS['TCA'][$table])) {
			$fields = $GLOBALS['TCA'][$table]['columns'];
			foreach ($fields as $field => $fieldConfig) {
				switch ($fieldConfig['config']['type']) {
					case 'group':
						if ($fieldConfig['config']['internal_type']==='file') {
							$fieldCriteriaConfig['field_compare_type_string']['TCEforms']['displayCond'] .= ','.$field;
							$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'] .= ','.$field;
						} elseif ($fieldConfig['config']['internal_type']==='db') {
							$setConfig = $fieldConfig;
							unset($setConfig['exclude']);
							$setConfig['label'] = 'LLL:EXT:kb_display/locallang.xml:pi_cached_criteria__compare_value';
							$setConfig['displayCond'] = 'FIELD:field_compare_compareField:REQ:false && FIELD:field_compare_usersel:REQ:false && FIELD:field_compare_field,0,-5:IN:'.$field;
							$setConfig['config']['size'] = 10;
							$setConfig['config']['autoSizeMax'] = 30;
							$setConfig['config']['maxitems'] = 50;
							$fieldCriteriaConfig['field_compare_value_'.$table.'_'.$field]['TCEforms'] = $setConfig;
						} else {
							print_r(array_keys($fieldCriteriaConfig));
							print_r($fieldConfig);
							die('TODO: Create code for setCriteriaFields_table / TCA-type: group');
						}
					break;
					case 'select':
						$setConfig = $fieldConfig;
						unset($setConfig['exclude']);
						$setConfig['label'] = 'LLL:EXT:kb_display/locallang.xml:pi_cached_criteria__compare_value';
						$setConfig['displayCond'] = 'FIELD:field_compare_compareField:REQ:false && FIELD:field_compare_usersel:REQ:false && FIELD:field_compare_field,0,-5:IN:'.$field;
						$setConfig['config']['minitems'] = 0;
						$setConfig['config']['maxitems'] = 40;
						$setConfig['config']['size'] = 10;
						$setConfig['config']['autoSizeMax'] = 20;
						$fieldCriteriaConfig['field_compare_value_'.$table.'_'.$field]['TCEforms'] = $setConfig;
					break;
					case 'check':
						$fieldCriteriaConfig['field_compare_value_bool']['TCEforms']['displayCond'] .= ','.$field;
					break;
					case 'text':
						$fieldCriteriaConfig['field_compare_type_string']['TCEforms']['displayCond'] .= ','.$field;
						$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'] .= ','.$field;
					break;
					case 'inline':
						// TODO: Write criteria-value-code for inline fields (database relation like)
					break;
					case 'input':
						$eval = t3lib_div::trimExplode(',', $fieldConfig['config']['eval'], 1);
						$eval = array_diff($eval, array('nospace', 'alphanum', 'alphanum_x', 'lower', 'unique', 'trim', 'required'));
						$eval = implode(',', $eval);
						switch ($eval) {
							case 'int':
								$fieldCriteriaConfig['field_compare_type_number']['TCEforms']['displayCond'] .= ','.$field;
								$fieldCriteriaConfig['field_compare_value_int']['TCEforms']['displayCond'] .= ','.$field;
							break;
							case '':
								$fieldCriteriaConfig['field_compare_type_string']['TCEforms']['displayCond'] .= ','.$field;
								$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'] .= ','.$field;
//echo								$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond']."<br />\n";
							break;
							case 'time':
								$fieldCriteriaConfig['field_compare_type_time']['TCEforms']['displayCond'] .= ','.$field;
								$fieldCriteriaConfig['field_compare_value_time']['TCEforms']['displayCond'] .= ','.$field;
							break;
							case 'datetime':
								$fieldCriteriaConfig['field_compare_type_date']['TCEforms']['displayCond'] .= ','.$field;
								$fieldCriteriaConfig['field_compare_value_datetime']['TCEforms']['displayCond'] .= ','.$field;
							break;
							case 'date':
								$fieldCriteriaConfig['field_compare_type_date']['TCEforms']['displayCond'] .= ','.$field;
								$fieldCriteriaConfig['field_compare_value_date']['TCEforms']['displayCond'] .= ','.$field;
							break;
							case 'double2':
								$fieldCriteriaConfig['field_compare_type_number']['TCEforms']['displayCond'] .= ','.$field;
								$fieldCriteriaConfig['field_compare_value_double']['TCEforms']['displayCond'] .= ','.$field;
							break;
							default:
								print_r(array_keys($fieldCriteriaConfig));
								print_r($fieldConfig);
								die('Invalid "eval" configuration "'.$eval.'" for field type "'.$fieldConfig['config']['type'].'" criteria config!');
							break;
						}
					break;
					case 'flex':
					case 'passthrough':
					break;
					default:
						print_r(array_keys($fieldCriteriaConfig));
						print_r($fieldConfig);
						die('No criteria-config for field type "'.$fieldConfig['config']['type'].'" defined!');
					break;
				}
			}
		}
	}

	public function replaceIncludes_recursive($array) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = $this->replaceIncludes_recursive($value);
			} else {
				$array[$key] = $this->replaceIncludes($value, $key);
				if ($array[$key]=='__unset__') {
					unset($array[$key]);
				}
			}
		}
		return $array;
	}


	public function replaceIncludes($value, $key) {
		if (strpos($value, 'includeXML:')===0) {
			$fileName = substr($value, strlen('includeXML:'));
			$file = t3lib_div::getFileAbsFileName($fileName);
			if (file_exists($file) && is_file($file)) {
				$key = $fileName;
				if (!isset($this->currentRecursion[$key])) {
					$key = 'default';
				}
				if (!$this->currentRecursion[$key]) {
					$value = '__unset__';
				} elseif (--$this->currentRecursion[$key]) {
					$data = t3lib_div::getURL($file);
					$xml = t3lib_div::xml2array($data);
					if (is_array($xml)) {
						$this->getFlexFormDS_postProcessDS($xml, false, false, false, false);
						$value = $xml;
					} else {
						die('Included XML file "'.$file.'" is not valid XML! Error: "'.$xml.'"');
					}
				} else {
					$value = '__unset__';
				}
			} else {
				die('Included XML file "'.$file.'" does not exist !');
			}
		}
		return $value;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_t3libbefunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_t3libbefunc.php']);
}

?>