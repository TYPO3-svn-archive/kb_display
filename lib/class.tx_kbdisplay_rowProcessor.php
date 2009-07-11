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
 * Class for fetching and processing the query results
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	kb_display
 */
class tx_kbdisplay_rowProcessor {
	private $parentObj = NULL;
	private $rootObj = NULL;
	private $queryFetcher = NULL;
	private $queryController = NULL;
	private $queryGenerator = NULL;
	private $useConfig = NULL;
	private $cObj = NULL;
	private $joinKeys = array();


	/**
	 * Initialize the object instance
	 *
	 * @param	object		A pointer to the parent object instance (The FE-plugin)
	 * @return	void
	 */
	public function init(&$parentObj, &$rootObj) {
		$this->parentObj = &$parentObj;
		$this->rootObj = &$rootObj;
		$this->queryController = &$this->parentObj;
		$this->queryFetcher = &$this->parentObj->get_queryFetcher();
		$this->queryGenerator = &$this->parentObj->get_queryGenerator();
		$this->useConfig = &$this->rootObj->useConfig;
		$this->cObj = clone($this->rootObj->cObj);
	}

	/**
	 * This method transforms all fetched result rows according to the transformations rules defined in TS
	 *
	 * @return	void
	 */
	public function transformResult() {
		$this->resultData = $this->queryFetcher->get_resultData();

		// Retrieve keys after which to combine rows from queryController / queryGenerator
		$this->aquireJoinKeys();

		// Transform rows according to TS-rules - perform separation in sub-array depening on table
		$resultRows = array();
		if (is_array($this->resultData)) {
			foreach ($this->resultData as $idx => $dataArray) {
				$tmpRow = $this->transformRow($dataArray);
				if ($tmpRow) {
					$resultRows[] = $tmpRow;
				}
			}
		}

		// Combine rows depending on joinKeys
		if (is_array($this->joinKeys) && count($this->joinKeys)) {
			$resultRows = $this->combineRows($resultRows);
		}

		if (is_array($this->useConfig['itemList.']['cObjects.']) && count($this->useConfig['itemList.']['cObjects.'])) {
			foreach ($resultRows as $idx => $dataArray) {
				$cObjects = $this->get_cObjects($dataArray);
				if (is_array($cObjects) && count($cObjects)) {
					$resultRows[$idx]['cObjects'] = $cObjects;
				}
			}
		}

		$this->resultData = $resultRows;
		return $this->resultData;
	}

	private function combineRows($resultRows) {
		$combinedRows = array();
		$remainingRows = array();
		foreach ($resultRows as $rowIdx => $resultRow) {
			$newKeys = false;
			foreach ($this->joinKeys as $joinTable => $joinIdents) {
				$compareIdents = $this->compareKeys[$joinTable];
				$joinKey = $this->getJoinKey($joinTable, $resultRow, $joinIdents);
//echo $joinTable."\n";
//print_r($resultRow);
//print_r($joinIdents);
//echo $joinKey."\n";
				if (!$combinedRows[$joinKey]) {
					$newKeys = true;
				}
				// TODO: Only works for tables having an uid
				$rowKey = $resultRow[$joinTable]['uid'];
//				$rowKey = $this->getRowKey($joinTable, $resultRow, $compareIdents);
				if (is_array($resultRow[$joinTable])) {
					$combinedRows[$joinKey][$rowKey] = $resultRow[$joinTable];
				}
//				unset($resultRows[$rowIdx][$joinTable]);
			}
//			if (!$newKeys && (count($resultRows[$rowIdx]) <= 2)) {
			if (!$newKeys) {
				unset($resultRows[$rowIdx]);
			}
		}
//print_r($resultRows);
//print_r($combinedRows);
		foreach ($resultRows as $rowIdx => $resultRow) {
			foreach ($this->joinKeys as $joinTable => $joinIdents) {
				$joinKey = $this->getJoinKey($joinTable, $resultRow, $joinIdents);
//echo $joinTable."\n";
//print_r($resultRow);
//print_r($joinIdents);
//echo $joinKey."\n";
				if ($combinedRows[$joinKey]) {
					$resultRows[$rowIdx][$joinTable.'_joined'] = $combinedRows[$joinKey];
				}
				unset($resultRows[$rowIdx][$joinTable]);
			}
		}
		return $resultRows;
	}

	function getRowKey($joinedTable, $resultRow, $compareIdents) {
		$rowData = array();
		foreach ($compareIdents as $compareTable => $compareFields) {
			foreach ($compareFields as $fieldData) {
				$table = $fieldData[0];
				$field = $fieldData[1];
				$rowData[$table][$field] = $resultRow[$table][$field];
			}
		}
		return md5(serialize($rowData));

	}

	function getJoinKey($joinedTable, $resultRow, $joinIdents) {
		$joinData = array();
		foreach ($joinIdents as $table => $fieldData) {
			$field = array_shift($fieldData);
			$joinData[$table][$field] = $resultRow[$table][$field];
		}
		return md5($joinedTable.serialize($joinData));
	}

	/**
	 * Wrapper method for transforming a single row, utilizes the transformation object to perform this task
	 *
	 * @return	void
	 */
	private function transformRow($data) {
		$this->row_cObj = clone($this->cObj);

		// Separate fields into subarrays depending on their tableIndex
		$data = $this->separateFields($data);

		// Set key "mainTable" as reference to table being queried
		$keys = array_keys($data);
		$firstKey = $keys[0];
		$data['mainTable'] = &$data[$firstKey];

		// Initialize cObject with transformed data
		$this->row_cObj->start($data);

		// process fields according to TypoScript-Setup
		$data = $this->processFields($data);

		return $data;
	}

	public function get_cObjects($dataArray = false, $config = false) {
		if (!$config) {
			$config = $this->useConfig['itemList.']['cObjects.'];
		}
		$result = array();
		if (is_array($config) && count($config)) {
			$this->row_cObj = clone($this->cObj);
			if ($dataArray) {
				$this->row_cObj->start($dataArray);
			}
			$this->row_cObj->data['caller'] = &$this;
			foreach ($config as $key => $subConfig) {
				if (substr($key, -1)!=='.') {
					$result[$key] = $this->row_cObj->cObjGetSingle($config[$key], $config[$key.'.']);
				}
			}
		}
		return $result;
	}

	private function aquireJoinKeys() {
		// Get all table indexes from query controller
		$tableIndexes = $this->queryController->get_tableIndexes();
		foreach ($tableIndexes as $tableIndex) {
			if ($tableIndex>0) {
				// If current index is an index of an additional table get joinInfo
				$joinInfo = $this->queryGenerator->get_tableJoinInfo($tableIndex);
				if ($joinInfo['combine']) {
//print_r($joinInfo);
					// When this joinInfo declares a "combine" - run through all criteriaKeys and append them to the joinKeys array
					foreach ($joinInfo['criteriaKeys'] as $idx => $joinKey) {
						$currentIndex = $joinKey['current']['index'];
						$currentTable = $this->queryController->get_tableName($currentIndex);
						$joinTable = $joinKey['table'];
						$joinField = $joinKey['field'];
						// TODO: This is currently not save when the same table is joined multiple times.
						// For this case the "index" value has to get respected
						$this->joinKeys[$currentTable][$joinTable][$joinField] = $joinField;
						$compareKey = $joinInfo['criteriaCompared'][$idx];
						$this->compareKeys[$currentTable][$joinTable][$joinField] = array($compareKey['table'], $compareKey['field']);
//print_r($this->joinKeys);
//print_r($this->compareKeys);
					}
				}
			}
		}
	}

	private function separateFields($data) {
		$result = array();
		foreach ($data as $field => $value) {
			if ($value===NULL) {
				continue;
			} elseif (is_array($value)) {
				$subResult = array();
				foreach ($value as $dataIdx => $dataRow) {
					if (is_array($dataRow)) {
						foreach ($dataRow as $subField => $subValue) {
							list($subField, $tableIdx) = explode('__', $subField);
							$subResult[$dataIdx][$subField] = $subValue;
						}
					}
				}
				$result[$field] = $subResult;
				$result[$field]['__isSubData'] = true;
			} else {
				$preField = $field;
				list($field, $tableIdx) = explode('__', $field);
				$resultName = $this->queryController->get_resultName($tableIdx);
				if (!$resultName) {
					$resultName = $this->queryController->get_tableName($tableIdx);
				}
				$result[$resultName][$field] = $value;
			}
		}
		return $result;
	}

	private function combinedFields($data)	{
		$result = array();
		foreach ($data as $table => $fields) {
			foreach ($fields as $field => $value) {
				$result[$table][$field] = $value;
				$result[$table.'__'.$field] = $value;
			}
		}
		return $result;
	}

	private function processFields($data) {
		$result = array();
		foreach ($data as $table => $fields) {
			if (is_array($fields)) {
				if ($fields['__isSubData']) {
					unset($fields['__isSubData']);
					foreach ($fields as $dataIdx => $dataRow) {
						if (is_array($dataRow)) {
							$this->row_cObj->data['current'] = $dataRow;
							foreach ($dataRow as $field => $value) {
								$this->set_processField($result[$table][$dataIdx], $table, $field, $value);
							}
						}
					}
				} else {
					foreach ($fields as $field => $value) {
						$this->set_processField($result[$table], $table, $field, $value);
					}
				}
			} else {
				$result[$table] = $fields;
			}
		}
		return $result;
	}


	private function set_processField(&$resultData, $table, $field, $value) {
		list($isMulti, $value, $procValue, $suffix) = $this->processField($table, $field, $value);
		if ($isMulti) {
			foreach ($procValue as $resultRow) {
				list($subIsMulti, $subValue, $subProcValue, $subSuffix) = $resultRow;
				if ($subProcValue) {
					$resultData[$field.'_'.$subSuffix] = $subProcValue;
				}
			}
		} else {
			if ($procValue) {
				$resultData[$field.'_'.$suffix] = $procValue;
			}
		}
		$resultData[$field] = $value;
	}


	private function processField($table, $field, $value, $config = false) {
		if (!$config) {
			$config = $this->useConfig['itemList.']['fields.'][$table.'.'][$field.'.'];
		}
		$suffix = 'proc';
		if (is_array($config)) {
			if ($config['multiWrap']) {
				$result = array();
				foreach ($config as $key => $subconf) {
					$key = preg_replace('/\.$/', '', $key);
					if (t3lib_div::testInt($key) && is_array($subconf)) {
						$result[] = $this->processField($table, $field, $value, $subconf);
					}
				}
				return array(true, $value, $result);
			}
			if ($config['suffix']) {
				$suffix = $config['suffix'];
			}
			return array(false, $value, $this->row_cObj->stdWrap($value, $config), $suffix);
		} else {
			return array(false, $value, '', $suffix);
		}
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_rowProcessor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_rowProcessor.php']);
}

?>
