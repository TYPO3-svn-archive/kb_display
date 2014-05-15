<?php
namespace thinkopen_at\kbDisplay\Hooks;
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


use \TYPO3\CMS\Core\Utility\GeneralUtility;

/** 
 * Hook method for "BackendUtility" which enables dynamic flexforms
 * @todo: Create doc comments
 * @todo: Use caching framework instead of creating file caches
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 */
class DynamicFlexforms extends \thinkopen_at\kbDisplay\Settings\FlexFieldParser {
	var $currentRecursion = false;
	var $maxRecursion = array(
		'EXT:kb_display/res/flexform_ds_pi_cached__subcriteria_tpl.xml' => 2,
		'default' => -1,
	);

	public function getFlexFormDS_postProcessDS(&$dataStructArray, $conf, $row, $table, $fieldName, $level = 0) {
		if ($level === 0) {
			$checksumData = array($conf, $table, $row, $fieldName);
			$checksum = md5(serialize($checksumData));
			$cacheFile = PATH_site.'typo3temp/kb_display/kb_display_DS_cache_'.$checksum.'.php';
			if (file_exists($cacheFile)) {
				include($cacheFile);
				$dataStructArray = $data;
				if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['kb_display']['debugDynamicFlexforms']) {
					GeneralUtility::devLog('Used cached flexform XML', 'kb_display', 0, $dataStructArray);
				}
				return;
			}
		}
			// Why this ???
		if ($level < 2) {
//		if ($conf) {
			$this->currentRecursion = $this->maxRecursion;
		}
		if (is_array($dataStructArray)) {
			$dataStructArray = $this->replaceIncludes_recursive($dataStructArray, $level);
		}
		if ($conf) {
				// comparing table join criterias
			if (is_array($dataStructArray) && is_array($dataStructArray['sheets']['sheet_tables']['ROOT']['el']['list_tables']['el']['item_table']['el']['list_criteria_section']['el']['list_criteria_item']['el'])) {
				$this->setCriteriaFields($table, $row, $dataStructArray['sheets']['sheet_tables']['ROOT']['el']['list_tables']['el']['item_table']['el']['list_criteria_section']['el']['list_criteria_item']['el']);
			}
			if (is_array($dataStructArray) && is_array($dataStructArray['sheets']['sheet_tables']['ROOT']['el']['list_tables']['el']['item_table']['el']['list_criteria_section']['el']['list_subcriteria']['el']['list_criteria_section']['el']['list_criteria_item']['el'])) {
				$this->setCriteriaFields($table, $row, $dataStructArray['sheets']['sheet_tables']['ROOT']['el']['list_tables']['el']['item_table']['el']['list_criteria_section']['el']['list_subcriteria']['el']['list_criteria_section']['el']['list_criteria_item']['el']);
			}

				// comparing "where" criterias
			if (is_array($dataStructArray) && is_array($dataStructArray['sheets']['sheet_criteria']['ROOT']['el']['list_criteria_section']['el']['list_criteria_item']['el'])) {
				$this->setCriteriaFields($table, $row, $dataStructArray['sheets']['sheet_criteria']['ROOT']['el']['list_criteria_section']['el']['list_criteria_item']['el']);
			}
			if (is_array($dataStructArray) && is_array($dataStructArray['sheets']['sheet_criteria']['ROOT']['el']['list_criteria_section']['el']['list_subcriteria']['el']['list_criteria_section']['el']['list_criteria_item']['el'])) {
				$this->setCriteriaFields($table, $row, $dataStructArray['sheets']['sheet_criteria']['ROOT']['el']['list_criteria_section']['el']['list_subcriteria']['el']['list_criteria_section']['el']['list_criteria_item']['el']);
			}

				// comparing filter criterias
			if (is_array($dataStructArray) && is_array($dataStructArray['sheets']['sheet_filters']['ROOT']['el']['list_filters_section']['el']['list_criteria_item']['el'])) {
				$this->setCriteriaFields($table, $row, $dataStructArray['sheets']['sheet_filters']['ROOT']['el']['list_filters_section']['el']['list_criteria_item']['el']);
			}
			if (is_array($dataStructArray) && is_array($dataStructArray['sheets']['sheet_filters']['ROOT']['el']['list_filters_section']['el']['list_subcriteria']['el']['list_criteria_section']['el']['list_criteria_item']['el'])) {
				$this->setCriteriaFields($table, $row, $dataStructArray['sheets']['sheet_filters']['ROOT']['el']['list_filters_section']['el']['list_subcriteria']['el']['list_criteria_section']['el']['list_criteria_item']['el']);
			}
		}
		if (($level === 0) && !file_exists($cacheFile)) {
			if (is_array($dataStructArray)) {
				$ok = $this->writeCacheFile($cacheFile, $dataStructArray);
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['kb_display']['debugDynamicFlexforms']) {
				GeneralUtility::devLog('Generated '.($ok ? '(and cached) ' : '').'flexform XML', 'kb_display', 0, $dataStructArray);
				if (!$ok) {
					GeneralUtility::devLog('Error caching flexform XML. Does "typo3temp/kb_display/" exist and is writable?', 'kb_display', 2);
				}
			}
		}
	}

	private function writeCacheFile($cacheFile, $currentData) {
		$cacheData = '<'.'?php'.chr(10);
		$cacheData .= '$data = Array('.chr(10);
		$cacheData .= $this->getArrayCode($currentData);
		$cacheData .= ');';
		$cacheData .= '?'.'>';
		return GeneralUtility::writeFile($cacheFile, $cacheData);
	}

	private function getArrayCode($currentData, $level = 0) {
		$code = '';
		foreach ($currentData as $key => $value) {
			$code .= str_repeat(chr(9), $level+1);
			$code .= '\''.$key.'\' => ';
			switch (gettype($value)) {
				case 'boolean':
					if ($value) {
						$code .= 'true';
					} else {
						$code .= 'false';
					}
				break;
				case 'double':
				case 'integer':
					$code .= $value;
				break;
				break;
				case 'string':
					$code .= '\''.addcslashes($value, '\'\\').'\'';
				break;
				case 'array':
					$code .= 'Array('.chr(10);
					$code .= $this->getArrayCode($value, $level+1);
					$code .= str_repeat(chr(9), $level+1).')';
				break;
				case 'NULL':
					$code .= 'NULL';
				break;
				case 'object':
					throw new \InvalidArgumentException('Variable type "object" not valid in DS-XML!', 1392242587);
				break;
				case 'resource':
					throw new \InvalidArgumentException('Variable type "resource" not valid in DS-XML!', 1392242597);
				break;
				case 'unknown type':
					throw new \InvalidArgumentException('Invalid variable type in DS-XML!', 1392242606);
				break;
			}
			$code .= ','.chr(10);
		}
		return $code;
	}

	private function setCriteriaFields($table, $row, &$fieldCriteriaConfig) {
		$flexXML = $row['pi_flexform'];
		$tables = array();
		if ($flexXML) {
			$flexData = GeneralUtility::xml2array($flexXML);
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
		if (is_array($GLOBALS['TCA'][$table])) {
			$fields = $GLOBALS['TCA'][$table]['columns'];
			foreach ($fields as $field => $fieldConfig) {
				switch ($fieldConfig['config']['type']) {
					case 'group':
						if ($fieldConfig['config']['internal_type']==='file') {
							$fieldCriteriaConfig['field_compare_string']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_string']['TCEforms']['displayCond'], $field);
							$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'], $field);
						} elseif ($fieldConfig['config']['internal_type']==='db') {
							$setConfig = $fieldConfig;
							unset($setConfig['exclude']);
							$setConfig['label'] = 'LLL:EXT:kb_display/locallang.xml:pi_cached_criteria__compare_value';
							$setConfig['displayCond'] = array(
								'and' => array(
									'1' => 'FIELD:field_compare_compareField:REQ:false',
									'2' => 'FIELD:field_compare_usersel:REQ:false',
									'3' => 'KB_DISPLAY_FIELD:field_compare_field,0,-5:IN:'.$field,
								)
							);
							$setConfig['config']['size'] = 10;
							$setConfig['config']['autoSizeMax'] = 30;
							$setConfig['config']['maxitems'] = 50;
							$fieldCriteriaConfig['field_compare_value_'.$table.'_'.$field]['TCEforms'] = $setConfig;
						} else {
							print_r(array_keys($fieldCriteriaConfig));
							print_r($fieldConfig);
							throw new \RuntimeException('TODO: Create code for setCriteriaFields_table / TCA-type: group other than "internal_type=file/db"', 1392242648);
						}
					break;
					case 'select':
						$setConfig = $fieldConfig;
						unset($setConfig['exclude']);
						$setConfig['label'] = 'LLL:EXT:kb_display/locallang.xml:pi_cached_criteria__compare_value';
						$setConfig['displayCond'] = array(
							'and' => array(
								'1' => 'FIELD:field_compare_compareField:REQ:false',
								'2' => 'FIELD:field_compare_usersel:REQ:false',
								'3' => 'KB_DISPLAY_FIELD:field_compare_field,0,-5:IN:'.$field,
							)
						);
						$setConfig['config']['minitems'] = 0;
						$setConfig['config']['maxitems'] = 40;
						$setConfig['config']['size'] = 10;
						$setConfig['config']['autoSizeMax'] = 20;
						unset($setConfig['config']['MM']);
						$fieldCriteriaConfig['field_compare_value_'.$table.'_'.$field]['TCEforms'] = $setConfig;
					break;
					case 'check':
						$fieldCriteriaConfig['field_compare_value_bool']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_bool']['TCEforms']['displayCond'], $field);
					break;
					case 'text':
						$fieldCriteriaConfig['field_compare_string']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_string']['TCEforms']['displayCond'], $field);
						$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'], $field);
					break;
					case 'inline':
						// TODO: Write criteria-value-code for inline fields (database relation like)
					break;
					case 'radio':
						$setConfig = $fieldConfig;
						unset($setConfig['exclude']);
						$setConfig['label'] = 'LLL:EXT:kb_display/locallang.xml:pi_cached_criteria__compare_value';
						$setConfig['displayCond'] = array(
							'and' => array(
								'1' => 'FIELD:field_compare_compareField:REQ:false',
								'2' => 'FIELD:field_compare_usersel:REQ:false',
								'3' => 'KB_DISPLAY_FIELD:field_compare_field,0,-5:IN:'.$field,
							),
						);
						$fieldCriteriaConfig['field_compare_value_'.$table.'_'.$field]['TCEforms'] = $setConfig;
					break;
					case 'input':
						$eval = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], 1);
						$eval = array_diff($eval, array('nospace', 'alphanum', 'alphanum_x', 'lower', 'unique', 'trim', 'required', 'md5', 'password'));

						$eval = implode(',', $eval);
						switch ($eval) {
							case 'int':
								$fieldCriteriaConfig['field_compare_number']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_number']['TCEforms']['displayCond'], $field);
								$fieldCriteriaConfig['field_compare_value_int']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_int']['TCEforms']['displayCond'], $field);
							break;
							case '':
								$fieldCriteriaConfig['field_compare_string']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_string']['TCEforms']['displayCond'], $field);
								$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond'], $field);
//								$fieldCriteriaConfig['field_compare_value_string']['TCEforms']['displayCond']."<br />\n";
							break;
							case 'time':
									// TODO: field_compare_time
								$fieldCriteriaConfig['field_compare_type_time']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_type_time']['TCEforms']['displayCond'], $field);
								$fieldCriteriaConfig['field_compare_value_time']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_time']['TCEforms']['displayCond'], $field);
							break;
							case 'datetime':
								$fieldCriteriaConfig['field_compare_date']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_date']['TCEforms']['displayCond'], $field);
								$fieldCriteriaConfig['field_compare_value_datetime']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_datetime']['TCEforms']['displayCond'], $field);
							break;
							case 'date':
								$fieldCriteriaConfig['field_compare_date']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_date']['TCEforms']['displayCond'], $field);
								$fieldCriteriaConfig['field_compare_value_date']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_date']['TCEforms']['displayCond'], $field);
							break;
							case 'double2':
								$fieldCriteriaConfig['field_compare_number']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_number']['TCEforms']['displayCond'], $field);
								$fieldCriteriaConfig['field_compare_value_double']['TCEforms']['displayCond'] = $this->addDisplayCondField($fieldCriteriaConfig['field_compare_value_double']['TCEforms']['displayCond'], $field);
							break;
							case 'nospace':
							case 'uniqueInPid':
							case 'required':
							break;
							default:
								GeneralUtility::devLog('Invalid "eval" configuration "'.$eval.'" for field type "'.$fieldConfig['config']['type'].'" criteria config!', 'kb_display', 3);
							break;
						}
					break;
					case 'flex':
					case 'passthrough':
					case 'user':
					break;
					default:
						GeneralUtility::devLog('No criteria-config for field type "'.$fieldConfig['config']['type'].'" defined!', 'kb_display', 3);
					break;
				}
			}
		}
	}

	public function replaceIncludes_recursive($array, $level) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = $this->replaceIncludes_recursive($value, $level);
			} else {
				$array[$key] = $this->replaceIncludes($value, $key, $level);
				if ($array[$key]=='__unset__') {
					unset($array[$key]);
				}
			}
		}
		return $array;
	}


	public function replaceIncludes($value, $key, $level) {
		if (strpos($value, 'includeXML:')===0) {
			$fileName = substr($value, strlen('includeXML:'));
			$file = GeneralUtility::getFileAbsFileName($fileName);
			if (file_exists($file) && is_file($file)) {
				$key = $fileName;
				if (!isset($this->currentRecursion[$key])) {
					$key = 'default';
				}
				if (!$this->currentRecursion[$key]) {
					$value = '__unset__';
				} elseif (--$this->currentRecursion[$key]) {
					$data = GeneralUtility::getURL($file);
					$xml = GeneralUtility::xml2array($data);
					if (is_array($xml)) {
						$this->getFlexFormDS_postProcessDS($xml, false, false, false, false, $level+1);
						$value = $xml;
					} else {
						throw new \InvalidArgumentException('Included XML file "'.$file.'" is not valid XML! Error: "'.$xml.'"', 1392242457);
					}
				} else {
					$value = '__unset__';
				}
			} else {
				throw new \InvalidArgumentException('Included XML file "'.$file.'" does not exist !', 1392242489);
			}
		}
		return $value;
	}

	protected function addDisplayCondField($displayCond, $field) {
		foreach ($displayCond as $key => $value) {
			if (is_array($value)) {
				$displayCond[$key] = $this->addDisplayCondField($value, $field);
			} else {
				$displayCond[$key] = str_replace('dummy', $field.',dummy', $value);
			}
		}
		return $displayCond;
	}

}
