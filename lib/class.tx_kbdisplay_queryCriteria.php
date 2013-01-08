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
 * Class handling the criteria elements of the BE plugin flexform
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbt3tris
 */
class tx_kbdisplay_queryCriteria {
	private $parentObj = null;
	private $rootObj = null;
	private $table = null;
	private $criterias_flexFormData = array();
	private $criterias = array();
	private $criteriaKeys = array();
	private $criteriaType = 'criterias';

	private $filterVars = array();
	private $filterValue = array();
	private $filter = false;
	private $filterOptions = array();

	private $search = false;
	private $searchWords = array();

	private $join_MM = array();

	/**
	 * Initialize the object instance
	 *
	 * @param	object		A pointer to the parent object instance (The FE-plugin)
	 * @return	void
	 */
	public function init(&$parentObj, &$rootObj, $criteriaType = 'criterias') {
		$this->parentObj = &$parentObj;
		$this->rootObj = &$rootObj;
		$this->queryGenerator = &$parentObj->get_queryGenerator();
		$this->useConfig = &$this->rootObj->useConfig;
		$this->cObj = clone($this->rootObj->cObj);
		$this->prefixId = $this->rootObj->prefixId;
		$this->criteriaType = $criteriaType;
		$_SERVER['QUERY_STRING'] = rawurldecode($_SERVER['QUERY_STRING']);
	}

	/**
	 * Set criteria flexform data
	 *
	 * @param	array		The flexform data of criterias set
	 * @return	void
	 */
	public function set_criterias($criteriaData) {
		$this->criterias_flexFormData = $criteriaData;
	}

	/**
	 * Sets the table which is being processed by the criteria object instance
	 *
	 * @param		string		The table being processed
	 * @return	void
	 */
	public function set_table($table) {
		$this->table = $table;
		t3lib_div::loadTCA($this->table);
		$this->tableIndex = $this->parentObj->get_tableIndex();
	}

	/**
	 * Parse criteria flexform data
	 *
	 * @return	integer		The number of criterias
	 */
	public function parse_criterias($overrideCriteria = false) {
		if ($overrideCriteria) {
			$parseCriteria = $overrideCriteria;
			$resultData = array();
		} else {
			$parseCriteria = $this->criterias_flexFormData;
			$resultData = &$this->criterias;
		}
		$parseCriteria = $overrideCriteria ? $overrideCriteria : $this->criterias_flexFormData;
		if (is_array($parseCriteria)) {
			foreach ($parseCriteria as $criteria) {
				if ($criteria['field_criteriaConnector'] && $criteria['list_criteria_section']) {
					$subResult = $this->parse_criterias($criteria['list_criteria_section']);
					$resultData[] = array(
						'connector' => $criteria['field_criteriaConnector'],
						'criterias' => $subResult,
					);
				} else {
					$crit = $this->parse_criteria($criteria);
					if ($crit) {
//						$this->criterias[] = $crit;
						$resultData[] = $crit;
					}
				}
			}
		}
		return $resultData;
	}

	/**
	 * Set criteria filter values
	 *
	 * @return	integer		The number of filter items
	 */
	public function set_filterValues() {
		$this->filter = true;
		$this->filterVars = $this->rootObj->piVars['filter'];
		if (is_array($this->criterias_flexFormData)) {
			foreach ($this->criterias_flexFormData as $filter) {
				$this->set_filterValue($filter);
				$this->fetch_filterOptions($filter);
			}
		}
	}

	/**
	 * Set search values
	 *
	 * @return	integer		The number of filter items
	 */
/*
Example: 
wort1,wort2,wort3
feld1,feld2,feld3
((feld1=wort1) OR (feld2=wort1) OR (feld3=wort1))
AND
((feld1=wort2) OR (feld2=wort2) OR (feld3=wort2))
AND
((feld1=wort3) OR (feld2=wort3) OR (feld3=wort3))
*/
	public function set_searchWords($caseSensitive = false) {
		$this->searchWords = $this->parentObj->get_searchWords();
		$this->search = true;
		$this->searchCase = $caseSensitive;
		if (is_array($this->searchWords) && count($this->searchWords)) {
			foreach ($this->searchWords as $searchWord) {
				$this->set_searchCriteria($searchWord);
			}
		}
	}


	/**
	 * Set the filter values from get/post parameters
	 *
	 * @param	array		A parsed criteria filter flexform definition
	 * @return	void
	 */
	private function set_filterValue($filter) {
		$field = $filter['field_compare_field'];
		if ($value = $this->filterVars[$field]) {
			$this->filterValue[$field] = $value;
		}
	}

	private function set_searchCriteria($searchWord) {
		if (is_array($this->criterias_flexFormData)) {
			$criterias = array();
			$whereParts = array();
			foreach ($this->criterias_flexFormData as $field) {
				$this->filterValue[$field] = $searchWord;
				$criteria = array(
					'field_compare_field' => $field,
				);
				$criterias[] = $this->parse_criteria($criteria, true);
			}
			foreach ($criterias as $criteria) {
				$where = '('.$criteria['operand1'].' '.$criteria['operator'].' '.$criteria['operand2'].')';
				$whereParts[] = $where;
			}
			$whereStr = implode(' OR ', $whereParts);
			if ($whereStr) {
				$this->criterias[] = array(
					'operand1' => $whereStr,
				);
			}
		}
	}

	/**
	 * Fetch filter options
	 *
	 * @param	array		A parsed criteria filter flexform definition
	 * @return	void
	 */
	private function fetch_filterOptions($filter) {
		$fieldOrig = $filter['field_compare_field'];
		list($field, $tableIdx) = explode('__', $fieldOrig);
		$tableIdx = intval($tableIdx);
		$table = $this->parentObj->get_tableName($tableIdx);
		$options = $this->getFieldOptions($table, $field, $fieldOrig);
		if ($this->useConfig['itemList.']['filter.'][$table.'.'][$field.'.'][$tableIdx.'.']['label']) {
			$label = $this->useConfig['itemList.']['filter.'][$table.'.'][$field.'.'][$tableIdx.'.']['label'];
		} else {
			$label = $GLOBALS['TCA'][$table]['columns'][$field]['label'];
		}
		$linkAll = $this->getOption_link($table, $field, '', 0, '', $fieldOrig);
		$this->filterOptions[$field] = array(
			'name' => $field,
			'valueAll' => '',
			'labelAll' => $GLOBALS['TSFE']->sL('LLL:EXT:kb_display/pi_cached/locallang.xml:filter_labelAll'),
			'linkAll' => $linkAll,
			'label' => $GLOBALS['TSFE']->sL($label),
			'options' => $options,
		);
	}

	/**
	 * Returns the internal array of filter options
	 *
	 * @return	array		An array of filter options
	 */
	public function get_filterOptions() {
		return $this->filterOptions;
	}

	/**
	 * This method retrieves all possible options for the passed field.
	 * This just makes sense for "select" or database relation fields.
	 *
	 * @param string	$table: The table of the field for which to fetch options
	 * @param string	$field: The name of the field (TCA) for which to fetch options
	 * @param string	$fieldKey: [TODO]
	 * @return array	All possible data options
	 */
	private function getFieldOptions($table, $field, $fieldKey) {
		t3lib_div::loadTCA($table);
		$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

		$options = array();
		if (is_array($config)) {
			switch ($config['type']) {
				case 'group':
					switch ($config['internal_type']) {
						case 'db':
							$foreignTables = t3lib_div::trimExplode(',', $config['allowed']);
							foreach ($foreignTables as $foreignTable) {
								$tmpOptions = $this->getOptions_query($table, $field, $foreignTable, '', $fieldKey);
								$options = array_merge($options, $tmpOptions);
							}
						break;
						case 'file':
							die('TODO: getFieldOptions not implemented for group::file');
						break;
					}
				break;
				case 'select':
					if ($foreignTable = $config['foreign_table']) {
						$options = $this->getOptions_query($table, $field, $foreignTable, $config['foreign_table_where'], $fieldKey);
					} else {
						$options = $this->getOptions_items($table, $field, $config['items'], $fieldKey);
					}
				break;
				case 'input':
					// TODO: Retrieve all distinct values (configurable length)
					$filterConfig = $this->useConfig['itemList.']['filter.'][$table.'.'][$field.'.'];
					$length = intval($filterConfig['length']);

						// Have sane defaults for every used variable
					$where = '';
					$fieldValue = '_option_value';
					if ($length) {
						$selectFields = 'DISTINCT(LEFT('.$field.', '.$length.')) AS _option_value';
					} else {
						$selectFields = 'DISTINCT('.$field.') AS _option_value';
					}

						// Set content of "$where" from TypoScript
					$where = $this->stdWrap($where, $filterConfig, 'where');

						// Set content of "$fieldValue" from TypoScript
					$fieldValue = $this->stdWrap($fieldValue, $filterConfig, 'fieldValue');

						// Set content of "$selectFields" from TypoScript
					$selectFields = $this->stdWrap($selectFields, $filterConfig, 'selectFields');

					$options = $this->getOptions_query($table, $field, $table, $where, $fieldKey, $selectFields, $fieldValue);
				break;
				case 'check':
					$options = $this->getOptions_check($table, $field, $fieldKey);
				break;
				default:
					die('TODO: getFieldOptions not implemented for field "'.$field.'" with "'.$config['type'].'" TCA type!');
				break;
			}
		} else {
			die('TODO: getFieldOptions not implemented for non TCA fields!');
		}
		return $options;
	}

	/**
	 * Returns options for a checkbox type field
	 *
	 * @param string	$table: The table of the field for which to return options
	 * @param string	$field: The name of the field (TCA) for which to return options
	 * @param string	$fieldKey: [TODO]
	 * @return array	The filter options for the passed criteria
	 */
	private function getOptions_check($table, $field, $fieldKey) {
		$link_yes = $this->getOption_link($table, $field, '', 'Y', array(), $fieldKey);
		$link_no = $this->getOption_link($table, $field, '', 'N', array(), $fieldKey);
		$selected_yes = $this->filterValue[$fieldKey] === 'Y' ? true : false;
		$selected_no = $this->filterValue[$fieldKey] === 'N' ? true : false;
		$options = array(
			array(
				'value' => 'Y',
				'label' => $GLOBALS['TSFE']->sL('LLL:EXT:kb_display/pi_cached/locallang.xml:filter_check_yes'),
				'link' => $link_yes,
				'selected' => $selected_yes,
			),
			array(
				'value' => 'N',
				'label' => $GLOBALS['TSFE']->sL('LLL:EXT:kb_display/pi_cached/locallang.xml:filter_check_no'),
				'link' => $link_no,
				'selected' => $selected_no,
			),
		);
		return $options;
	}

	/**
	 * Returns options for a select box with predefined items
	 *
	 * @param string	$table: The table of the field for which to return options
	 * @param string	$field: The name of the field (TCA) for which to return options
	 * @param string	$fieldKey: [TODO]
	 * @return array	The filter options for the passed criteria
	 */
	private function getOptions_items($table, $field, $items, $fieldKey) {
		$options = array();

		$params = array(
			'table' => $table,
			'field' => $field,
			'items' => $items,
			'fieldKey' => $fieldKey,
		);

		foreach ($items as $key => $item) {
			$link = $this->getOption_link($table, $field, '', $item[1], $item, $fieldKey);
			$selected = intval($this->filterValue[$fieldKey])==intval($item[1]) ? true : false;
			$option = array(
				'value' => $item[1],
				'label' => $item[0],
				'link' => $link,
				'selected' => $selected,
			);

				// ---- Call hook which allows to alter the options ---------- begin ------------
			$params['option'] = &$option;
			$params['item'] = $item;
			$this->rootObj->hook('queryCriteria/getOptions_items/processRow', $params);
				// ---- Call hook which allows to alter the options ---------- end --------------

			$options[] = $option;
		}

			// ---- Call hook which allows to alter the result ---------- begin ------------
		unset($params['option']);
		unset($params['item']);
		$params['options'] = &$options;
		$this->rootObj->hook('queryCriteria/getOptions_items/alterResult', $params);
			// ---- Call hook which allows to alter the result ---------- end --------------

		return $options;

	}

	/**
	 * Returns options for a items from a table
	 *
	 * @param string	$table: The table of the field for which to return options
	 * @param string	$field: The name of the field (TCA) for which to return options
	 * @param string	$foreignTable: The name of the foreign table from which to fetch the options
	 * @param string	$where: An additional where part for the table from which to fetch options
	 * @param string	$fieldKey: [TODO]
	 * @return array	The filter options for the passed criteria
	 */
	private function getOptions_query($table, $field, $foreignTable, $where, $fieldKey, $selectFields = '*', $fieldValue = 'uid') {
		$options = array();
		$storagePid = $GLOBALS['TSFE']->getStorageSiterootPids();
		$where = str_replace('###STORAGE_PID###', intval($storagePid['_STORAGE_PID']), $where);
		$wgolParts = $GLOBALS['TYPO3_DB']->splitGroupOrderLimit($where);
		$enableField = $GLOBALS['TSFE']->sys_page->enableFields($foreignTable);

		$queryParts = array(
			'SELECT' => $selectFields,
			'FROM' => $foreignTable,
			'WHERE' => '1=1 '.$enableField.$wgolParts['WHERE'],
			'GROUPBY' => $wgolParts['GROUPBY'],
			'ORDERBY' => $wgolParts['ORDERBY'],
			'LIMIT' => $wgolParts['LIMIT'],
		);
			// ---- Call hook which allows to alter the "getOptions" query ---------- begin ------------
		$params = array(
			'table' => $table,
			'field' => $field,
			'foreignTable' => $foreignTable,
			'where' => $where,
			'fieldKey' => $fieldKey,
			'fieldValue' => $fieldValue,
			'queryParts' => &$queryParts,
			'criteriaObject' => &$this,
		);
		$this->rootObj->hook('queryCriteria/getOptions_query/alterQuery', $params);
			// ---- Call hook which allows to alter the "getOptions" query --------- end --------------

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['kb_display']['debugFilterQuery']) {
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
			$GLOBALS['TYPO3_DB']->debugOutput = true;
			t3lib_div::devLog('Prepared filter query', 'kb_display', 0, $queryParts);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['kb_display']['debugFilterQuery']) {
			if ($result) {
				t3lib_div::devLog('Filter query executed successfully', 'kb_display', -1, array($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery));
			} else {
				t3lib_div::devLog('Filter query failed', 'kb_display', 3, array($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery));
			}
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = false;
			$GLOBALS['TYPO3_DB']->debugOutput = false;
		}

		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($foreignTable == 'pages') {
					$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
				} else {
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($foreignTable, $row, $GLOBALS['TSFE']->sys_language_content);
				}
				$label = $this->getOption_label($table, $field, $foreignTable, $row);
				$link = $this->getOption_link($table, $field, $foreignTable, $row[$fieldValue], $row, $fieldKey);
				$selected = ( $this->filterValue[$fieldKey] === $row[$fieldValue] ) ? true : false;
				
				$option = array(
					'value' => $row[$fieldValue],
					'label' => $label,
					'link' => $link,
					'selected' => $selected,
				);

					// ---- Call hook which allows to alter the options ---------- begin ------------
				$params['option'] = &$option;
				$params['row'] = $row;
				$this->rootObj->hook('queryCriteria/getOptions_query/processRow', $params);
					// ---- Call hook which allows to alter the options ---------- end --------------

				$options[] = $option;
			}
		}

			// ---- Call hook which allows to alter the result ---------- begin ------------
		unset($params['option']);
		$params['options'] = &$options;
		$this->rootObj->hook('queryCriteria/getOptions_query/alterResult', $params);
			// ---- Call hook which allows to alter the result ---------- end --------------

		return $options;
	}

	/**
	 * Returns the link for the passed row
	 *
	 * @param		string		The table for which to return a filter link for one of its records
	 * @param		string		The field for which to return a filter link
	 * @param		string		The foreign which is selected in this filter link
	 * @param		string		The value which gets filtered
	 * @param		array		The database row for which to return a link
	 * @param		string		The field key like it should get used as GET/POST variable name
	 * @return		string		The link for the passed row
	 */
	private function getOption_link($table, $field, $foreignTable, $value, $data, $fieldKey) {
		if ($linkConfig = $this->useConfig['itemList.']['filter.'][$table.'.'][$field.'.']['link.']) {
			$this->cObj->data['link_table'] = $table;
			$this->cObj->data['link_field'] = $field;
			$this->cObj->data['link_foreignTable'] = $foreignTable;
			$this->cObj->data['link_value'] = $value;
			$this->cObj->data['link_data'] = $data;
			$this->cObj->data['link_fieldKey'] = $fieldKey;
			return $this->cObj->typoLink_URL($linkConfig);
		} else {
			$linkConfig = array(
				'parameter.' => array(
					'data' => 'TSFE:id',
				),
				'useCacheHash' => 1,
			);
			$addQueryString = array();		
			if ($this->useConfig['itemList.']['filter.'][$table.'.'][$field.'.']['addQueryString'] || ($addQueryString = $this->useConfig['itemList.']['filter.'][$table.'.'][$field.'.']['addQueryString.'])) {
				$linkConfig['addQueryString'] = 1;
				$linkConfig['addQueryString.'] = $addQueryString;
			}
			if ($value) {
				$linkConfig['additionalParams'] = '&'.$this->prefixId.'[filter]['.$fieldKey.']='.$value;
				$linkConfig['addQueryString.']['exclude'] .= ($linkConfig['addQueryString.']['exclude']?',':'').$this->prefixId.'[page]';
			} else {
//				$linkConfig['additionalParams'] = '&'.$this->prefixId.'[filter]['.$fieldKey.']=';
				$linkConfig['addQueryString.']['exclude'] .= ($linkConfig['addQueryString.']['exclude']?',':'').$this->prefixId.'[page],'.$this->prefixId.'[filter]['.$fieldKey.']';
			}
			$link = $this->cObj->typoLink_URL($linkConfig);
			return $link;
		}
	}

	/**
	 * Returns the label for the passed row
	 *
	 * @param		string		The table for which to return a label for one of its records
	 * @param		array			The database row for which to return a label
	 * @return	string		The label for the passed row
	 */
	private function getOption_label($table, $field, $foreignTable, $data) {
		if ($labelField = $this->useConfig['itemList.']['filter.'][$table.'.'][$field.'.']['labelField']) {
			return $data[$labelField];
		} elseif ($data['_option_value']) {
			return $data['_option_value'];
		} elseif ($labelField = $GLOBALS['TCA'][$foreignTable]['ctrl']['label']) {
			return $data[$labelField];
		} else {
			return 'No label';
		}
	}

	/**
	 * Parses an flexform criteria into a where-definition array suitable for a query object instance
	 *
	 * @param	array		A parsed criteria flexform definition
	 * @return	array		The where definition for a criteria
	 */
	private function parse_criteria($criteria, $noMM = false) {

		/*
		 * Call hook which allows changing the behaviour of this method
		 *
		 * @hook queryCriteria/parse_criteria/methodStart
		 */
		$params = array(
			'criteria' => &$criteria,
			'noMM' => &$noMM,
		);
		$this->rootObj->hook('queryCriteria/parse_criteria/methodStart', $params);
		// EOF: hook - queryCriteria/parse_criteria/methodStart

		$field = $criteria['field_compare_field'];
		$value = $this->filterValue[$field];
		if ($this->filter && !$value) {
			return false;
		}
		list($field, $tableIdx) = explode('__', $field);
		$tableIdx = intval($tableIdx);
		$table = $this->parentObj->get_tableName($tableIdx);
		$criteria['operand1']['index'] = $tableIdx;
		$criteria['operand1']['table'] = $table;
		$criteria['operand1']['field'] = $field;
		$criteria['operand1']['current']['table'] = $this->table;
		$criteria['operand1']['current']['index'] = $this->tableIndex;
		$this->criteriaKeys[] = $criteria['operand1'];

		$compareField = $criteria['field_compare_compareField'];
		list($compareField, $compareTableIdx) = explode('__', $compareField);
		$compareTableIdx = intval($compareTableIdx);
		$compareTable = $this->parentObj->get_tableName($compareTableIdx);
		$criteria['operand2']['index'] = $compareTableIdx;
		$criteria['operand2']['table'] = $compareTable;
		$criteria['operand2']['field'] = $compareField;
		$this->criteriaCompared[] = $criteria['operand2'];

		$criteria['filter'] = $this->filter;
		$criteria['search'] = $this->search;
		$criteria['searchCase'] = $this->searchCase;
		$value = $GLOBALS['TYPO3_DB']->quoteStr($value, $table);
		$value = $GLOBALS['TYPO3_DB']->escapeStrForLike($value, $table);
		$criteria['filterValue'] = $value;

		if ($GLOBALS['TSFE']->loginUser) {
			$criteria['fe_user'] = &$GLOBALS['TSFE']->fe_user->user;
		} else {
			$criteria['fe_user'] = false;
		}

		$criteria['TSFE'] = &$GLOBALS['TSFE'];

		$type = $this->getFieldCompareType($field, $table);

		if ($type===NULL) {
			return false;
		}

		if (!$type) {
			die('No compare type for field "'.$field.'" known !');
		}

		$MM = '';
		$MM_idx = count($this->join_MM);
		if (is_array($type)) {
			if ($type['MM']) {
				$MM = $criteria['MM'] = $type['MM'];
				$type = $type['type'];
//				$criteria['operand1']['index'] = $tableIdx;
				$criteria['operand1']['foreign']['index'] = $criteria['operand1']['index'];
				$criteria['operand1']['foreign']['table'] = $criteria['operand1']['table'];
				$criteria['operand1']['foreign']['field'] = $criteria['operand1']['field'];
				$criteria['operand1']['index'] = $MM_idx;
				$criteria['operand1']['table'] = $MM;
				$criteria['operand1']['field'] = 'uid_foreign';
				$criteria['operand1']['current']['table'] = $this->table;
				$criteria['operand1']['current']['index'] = $this->tableIndex;
				array_pop($this->criteriaKeys);
				$this->criteriaKeys[] = $criteria['operand1'];
			} elseif ($type['CSV']) {
				$type = $type['type'].'_csv';
			} else {
				die('ERROR: Internal problem');
			}
		}
		$criteria['type'] = $type;

		$smarty = $this->rootObj->get_smartyClone();
		$templateDir = PATH_kb_display.'compareTypes/';
		$smarty->setSmartyVar('template_dir', $templateDir);
		$smarty->assign('criteria', $criteria);

		/*
		 * Call hook which allows interfering the compare XML process before rendering
		 *
		 * @hook queryCriteria/parse_criteria/preRender
		 */
		$params = array(
			'criteria' => &$criteria,
			'smarty' => &$smarty,
			'type' => &$type,
			'templateDir' => &$templateDir,
			'MM' => &$MM,
			'noMM' => &$noMM,
			'criteriaObject' => &$this,
		);
		$this->rootObj->hook('queryCriteria/parse_criteria/preRender', $params);
		// EOF: hook - queryCriteria/parse_criteria/preRender

		if ($file = t3lib_div::getFileAbsFileName($criteria['field_compare_custom'])) {
			$smarty->setSmartyVar('template_dir', dirname($file));
			$whereXML = $smarty->display(basename($file), '', md5($file));
		} else {
			$whereXML = $smarty->display($type.'/compareType.tpl', '', md5($templateDir));
		}
// echo $whereXML;
		$whereData = t3lib_div::xml2array($whereXML);

		/*
		 * Call hook which allows interfering the compare XML process after rendering
		 *
		 * @hook queryCriteria/parse_criteria/postRender
		 */
		$params = array(
			'criteria' => &$criteria,
			'smarty' => &$smarty,
			'type' => &$type,
			'templateDir' => &$templateDir,
			'MM' => &$MM,
			'noMM' => &$noMM,
			'whereData' => &$whereData,
			'criteriaObject' => &$this,
		);
		$this->rootObj->hook('queryCriteria/parse_criteria/postRender', $params);
		// EOF: hook - queryCriteria/parse_criteria/postRender

		if (!is_array($whereData)) {
			echo $whereXML."\n<br />\n";
			print_r($whereXML);
			die('Invalid criteria where-XML for field "'.$field.'" of type "'.$type.'" !');
		}

		if ($MM && !$noMM) {
			$joinCriteria = array(
				array(
					'operand1' => '`'.$table.'__'.$tableIdx.'`.`uid`',
					'operator' => '=',
					'operand2' => '`'.$MM.'__'.$MM_idx.'`.`uid_local`',
				),
			);
			$this->join_MM[] = array(
				'MM' => $MM,
//				'index' => $tableIdx,
				'index' => $MM_idx,
				'criteria' => $joinCriteria,
			);
		} elseif ($MM) {
			die('MM relations disabled for search fields. Add additional table!');
		}

		/*
		 * Call hook which allows changing any of the results made for parsing this criteria
		 *
		 * @hook queryCriteria/parse_criteria/methodEnd
		 */
		$params = array(
			'criteria' => &$criteria,
			'smarty' => &$smarty,
			'type' => &$type,
			'templateDir' => &$templateDir,
			'MM' => &$MM,
			'noMM' => &$noMM,
			'whereData' => &$whereData,
			'criteriaObject' => &$this,
		);
		$this->rootObj->hook('queryCriteria/parse_criteria/methodEnd', $params);
		// EOF: hook - queryCriteria/parse_criteria/methodEnd

		return $whereData;
	}

	/**
	 * This method returns the compare type (boolean, list, datetime) for the passed field/table combination
	 *
	 * @param	string		The TCA name of the field for which to retrieve compare types
	 * @param	table		The TCA name of the table in which field from previous param can be found
	 * @return	string		The compare type
	 */
	private function getFieldCompareType($field, $table) {
		// TODO: Make this configurable !
		t3lib_div::loadTCA($table);
		$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

		$type = '';
		if (is_array($config)) {
			switch ($config['type']) {
				case 'check':
					$type = 'boolean';
				break;
				case 'group':
					if ($config['internal_type']=='file') {
						$type = 'string';
						break;
					}
				case 'select':
					if ($config['MM']) {
						$type = array(
							'type' => 'list',
							'MM' => $config['MM'],
						);
						return $type;
					} elseif ($config['maxitems']>1) {
						$type = array(
							'type' => 'list',
							'CSV' => true,
						);
					} else {
						$type = 'list';
					}
				break;
				case 'input':
					$type = $this->getFieldCompareType_input($field, $table, $config);
				break;
				case 'text':
					$type = 'string';
				break;
				default:
					die('No compare type for TCA-Type "'.$config['type'].'" known !');
				break;
			}
		} else {
			switch ($field) {
				case 'uid':
				case 'pid':
					$type = 'list';
				break;
				case 'deleted':
					$type = 'boolean';
				break;
				case 'tstamp':
				case 'crdate':
					$type = 'timestamp';
				break;
				default:
					$type = 'string';
					// ToDo: Log this case
//					die('Field "'.$field.'" is not in TCA or has no default compare-type.');
				break;
			}
		}
		return $type;
	}

	/**
	 * This method returns the compare type (boolean, list, datetime, integer) for an TCA "input" field of the passed field/table combination
	 *
	 * @param	string		The TCA name of the field for which to retrieve compare types
	 * @param	table		The TCA name of the table in which field from previous param can be found
	 * @param		array			The TCA configuration for the field being processed
	 * @return	string		The compare type
	 */
	private function getFieldCompareType_input($field, $table, $config) {
		$eval = t3lib_div::trimExplode(',', $config['eval'], 1);
		$eval = array_diff($eval, array('required', 'trim', 'lower', 'alphanum'));
		if (!count($eval)) {
			return 'string';
		} elseif (in_array('int', $eval)) {
			return 'integer';
		} elseif (in_array('date', $eval)) {
			return 'timestamp';
		} elseif (in_array('datetime', $eval)) {
			return 'timestamp';
		} elseif (in_array('time', $eval)) {
			return 'timestamp';
		} else {
			die('No compare known for TCA "input" field with evaluation "'.$config['eval'].'"!');
		}
	}

	/**
	 * Sets the parsed criterias in the queryGenerator object instance using passed combination operator
	 *
	 * @param	string		The combination operator (AND/OR) for the parsed criterias
	 * @return	void
	 */
	public function setQuery_criteria($connector) {
/*
		if (is_array($this->join_MM) && count($this->join_MM)) {
			foreach ($this->join_MM as $joinTable) {
				$this->queryGenerator->set_table($joinTable['MM'], $joinTable['index'], '', 'join');
			}
		}
*/
/*
		DEBUG
		if ($this->filter) {
			print_r($this->criterias);
//			exit();
		}
*/
		$this->queryGenerator->set_criteria($this->criterias, $connector, $this->criteriaType);
	}

	/**
	 * Sets tables which have to get joined
	 *
	 * @return	void
	 */
	public function set_joinTables($joinType = false) {
		if (is_array($this->join_MM) && count($this->join_MM)) {
			foreach ($this->join_MM as $idx => $joinTable) {
				if ($joinType == 'query') {
					$this->queryGenerator->set_table($joinTable['MM'], $joinTable['index'].'_MM', '', 'join');
					$this->queryGenerator->set_criteria($joinTable['criteria'], 'AND');
				} else {
					$this->queryGenerator->set_table($joinTable['MM'], $joinTable['index'].'_MM', '', 'leftjoin');
					$this->queryGenerator->set_onClause($joinTable['criteria'], 'AND', $joinTable['index'].'_MM', array());
				}
			}
		}
	}

	/**
	 * Sets the parsed criterias as ON claus in the queryGenerator object instance using passed combination operator and combine/merge specifications
	 *
	 * @param	string		The combination operator (AND/OR) for the parsed ON clause criterias
	 * @param	integer		The index of the joined table for which to set this ON clause criterias
	 * @param	boolean		Whether to combine results as specified in joinInfo or not
	 * @return	void
	 */
	public function setQuery_onClause($connector, $tableIdx, $combineResults) {

		$joinInfo = array(
			'combine' => $combineResults,
			'criteriaKeys' => $this->criteriaKeys,
			'criteriaCompared' => $this->criteriaCompared,
		);
		$this->queryGenerator->set_onClause($this->criterias, $connector, $tableIdx, $joinInfo);
	}

	/**
	 * Do stdWrap processing in local object context
	 *
	 * @param string	$value: The current value to be stdWrapped
	 * @param array		$config: TypoScript configuration which includes the key/property to be used for wrapping
	 * @param string	$propertyName: The property in above TypoScript configuration array which should get used for wrapping
	 * @return string	The passed $value variable with stdWrap properties of $propertyName from within $configuration applied
	 */
	private function stdWrap($value, $config, $propertyName) {
		if ($config[$propertyName]) {
			$value = $config[$propertyName];
		}
		if ($config[$propertyName.'.']) {
			$local_cObj = clone($this->rootObj->cObj);
			$value = $local_cObj->stdWrap($value, $config[$propertyName.'.']);
		}
		return $value;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryCriteria.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryCriteria.php']);
}

?>
