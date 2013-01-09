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
require_once(PATH_kb_display.'lib/class.tx_kbdisplay_queryTable.php');


/**
 * Controller class for the process of query generating
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	kb_display
 */
class tx_kbdisplay_queryController extends tx_kbdisplay_flexFields {
	private $parentObj = null;
	private $rootObj = null;
	private $queryGenerator = null;
	private $queryFetcher = null;
	private $rowProcessor = null;
	private $sub_queryGenerators = array();

	protected $extraTablesDef = array();
	protected $mainTablesDef = array();
	protected $tableObjects = array();
	protected $resultData = array();

	protected $sub_queryResults = array();

	protected $isSearch = false;
	protected $searchWords = array();

	/**
	 * Initialize the object instance
	 *
	 * @param	object		A pointer to the parent object instance (The FE-plugin)
	 * @return	void
	 */
	public function init(&$parentObj, &$rootObj) {
		$this->parentObj = &$parentObj;
		$this->rootObj = &$rootObj;
		$this->flexData = $this->parentObj->cObj->data['pi_flexform'];

		// Initialize the "queryGenerator" object instance
		$this->initObject_queryGenerator();
		$this->set_limit();
		// Initialize the "queryFetcher" object instance (not a clone of Terry Fletcher ;)
		// Hmm. I always seem to mix those two up. I tought "Terry Fletcher" would be the person
		// in the TV series: "Murder, she wrote" (German: Mord ist ihr Hobby).
		// But after looking it up now on Wikipedia I found that the main actor of this TV series
		// is named "Jessica Beatrice Fletcher". There is also another person sounding similar:
		// "Terry Pratchett" who is the author of the "Discworld" fantasy novels.
		// Maybe queryFetcher uhm ... Terry Fetcher is some illegitimate child of both :)
		$this->initObject_queryFetcher();
		// Initialize the "rowProcessor" object instance
		$this->initObject_rowProcessor();
		$this->tableObjects = array();
	}

	/**
	 * Sets the row limit for SELECT in the queryGenerator object instance
	 *
	 * @return	void
	 */
	protected function set_limit() {
		$limit = $this->rootObj->itemsPerPage;
		if ($limit) {
			$this->queryGenerator->set_limit($limit);
			$page = $this->rootObj->current_page;
			if ($page) {
				$offset = $limit*$page;
				$this->queryGenerator->set_offset($offset);
			}
		}
	}

	/**
	 * Parse the flexform into data for main table and an array of extra tables
	 *
	 * @return	void
	 */
	public function parseFlexform() {
		$this->rootObj->hook('queryController/parseFlexform/begin');
		$additionalTables = $this->flexData['data']['sheet_tables']['lDEF']['list_tables']['el'];
		$this->extraTablesDef = $this->parseSectionElements($additionalTables);

		$mainTable = $this->parentObj->pi_getFFvalue($this->flexData, 'field_table', 'sDEF');
		$checkEnableDefault = intval($this->parentObj->pi_getFFvalue($this->flexData, 'field_checkEnableDefault', 'sDEF'));
		$this->show_hidden = intval($this->parentObj->pi_getFFvalue($this->flexData, 'field_showHidden', 'sDEF'));
		$checkEnableTime = intval($this->parentObj->pi_getFFvalue($this->flexData, 'field_checkEnableTime', 'sDEF'));
		$checkEnableAccess = intval($this->parentObj->pi_getFFvalue($this->flexData, 'field_checkEnableAccess', 'sDEF'));
		$criteriaConnector = $this->parentObj->pi_getFFvalue($this->flexData, 'field_criteriaConnector', 'sheet_criteria');
		$filtersConnector = $this->parentObj->pi_getFFvalue($this->flexData, 'field_filtersConnector', 'sheet_filters');
		$searchFields = $this->parentObj->pi_getFFvalue($this->flexData, 'field_search_fields', 'sheet_search');
		$searchFields = t3lib_div::trimExplode(',', $searchFields, 1);
		$fields_groupBy = $this->parentObj->pi_getFFvalue($this->flexData, 'field_groupBy_fields', 'sheet_groupBy');
		$this->isSearch = ( is_array($searchFields) && count($searchFields) ) ? true : false;
		if ($this->isSearch) {
			$this->parse_searchWords();
		}

		$mainCriteriaArray = $this->flexData['data']['sheet_criteria']['lDEF']['list_criteria_section']['el'];
		$mainCriteria = $this->parseSectionElements($mainCriteriaArray, 'vDEF', 1);

		$mainFiltersArray = $this->flexData['data']['sheet_filters']['lDEF']['list_filters_section']['el'];
		$mainFilters = $this->parseSectionElements($mainFiltersArray);

		$array_main_orderBy = $this->flexData['data']['sheet_sorting']['lDEF']['list_sorting_section']['el'];
		$this->main_orderBy = $this->parseSectionElements($array_main_orderBy);

		if ($this->rootObj->mode === 'singleView') {
			if (is_array($this->rootObj->showUids) && (count($this->rootObj->showUids)>1)) {
				$showUids = array();
				foreach ($this->rootObj->showUids as $idx => $showUid) {
					if ($showUid) {
						$showUids[] = intval($showUid);
					}
				}
				if (count($showUids)) {
					$mainCriteria[] = $this->getCriteria_singleView(implode(',', $showUids), $idx);
				}
			} else {
				$mainCriteria[] = $this->getCriteria_singleView($this->rootObj->showUid);
			}
		}

		$this->enableChecks = array(
			'check_enableDefault' => $checkEnableDefault,
			'check_enableTime' => $checkEnableTime,
			'check_enableAccess' => $checkEnableAccess,
		);

		$this->mainTablesDef = array(
			'field_table' => $mainTable,
			'field_jointype' => 'main',
			'field_criteriaConnector' => $criteriaConnector,
			'field_filtersConnector' => $filtersConnector,
			'list_criteria_section' => $mainCriteria,
			'list_filters_section' => $mainFilters,
			'list_orderBy_section' => $this->main_orderBy,
			'field_search_fields' => $searchFields,
			'field_groupBy_fields' => $fields_groupBy,
		);
		$this->mainTablesDef = array_merge($this->mainTablesDef, $this->enableChecks);
		$this->rootObj->hook('queryController/parseFlexform/end');
	}

	/*
	 * This method retrieves the search words from a GET/POST var an performs minor parsing
	 *
	 *
	 * @return	void
	 */
	function parse_searchWords() {
		$searchString = $this->rootObj->piVars['search'];
		$tmp_searchWords = preg_split('/[,\s]/', $searchString);
		$this->searchWords = array();
		if (is_array($tmp_searchWords) && count($tmp_searchWords)) {
			foreach ($tmp_searchWords as $searchWord) {
				$searchWord = trim($searchWord);
				if ($searchWord) {
					$this->searchWords[] = $searchWord;
				}
			}
		}	
	}

	/*
	 * This method returns parsed search words
	 *
	 *
	 * @return	array		An array of search words
	 */
	public function get_searchWords() {
		return $this->searchWords;
	}

	/*
	 * This method sets a criteria defining which elements to show for singleView
	 *
	 *
	 * @param	integer/string	The uid or a comma separated list of uids to show
	 * @param	integer		The index of the table for which to set the criteria
	 * @return	void
	 */
	private function getCriteria_singleView($showUid, $idx = 0) {
		$index = sprintf('%03d', $idx);
		return array(
			'field_compare_field' => 'uid__'.$index,
			'field_compare_value_uid' => preg_replace('/[^0-9,]/', '', $showUid),
		);
	}

	/**
	 * Init object instance for the query tables
	 *
	 * @return	integer		An index of the created table object instance
	 */
	private function initObject_queryTables() {
		$idx = count($this->tableObjects);
		$obj = t3lib_div::makeInstance('tx_kbdisplay_queryTable');
		$obj->init($this, $this->rootObj);
		$this->tableObjects[$idx] = &$obj;
		return $idx;
	}

	/**
	 * Init an object instance for the query table, and transfer flexform information to it
	 *
	 * @param	integer		The number of the extra table for which to transfer flexform information.
					If not set the information of the main table will get transfered.
	 * @return	integer		An index of the created table object instance
	 */
	private function tables_transferData($extraIdx = false) {
		if ($extraIdx !== false) {
			$data = $this->extraTablesDef[$extraIdx];
		} else {
			$data = $this->mainTablesDef;
		}
		if ($data) {
			$idx = $this->initObject_queryTables();
			$data = array_merge($data, $this->enableChecks);
			$data['list_orderBy_section'] = $this->main_orderBy;
			$this->tableObjects[$idx]->set_flexData($data);
			return $idx;
		} else {
			return false;
		}
	}

	/**
	 * Returns the array of filter options
	 *
	 * @return	array		An array of filter options
	 */
	public function get_filterOptions() {
		return $this->tableObjects[0]->get_filterOptions();
	}

	/**
	 * Init an object instance for the main query table, and transfer flexform information to it
	 *
	 * @return	integer		An index of the created table object instance
	 */
	public function tables_main_transferData() {
		return $this->tables_transferData();
	}

	/**
	 * Init an object instance for each extra query table, and transfer its flexform information to it
	 *
	 * @return	array		An array with indexes for all initialized table objects
	 */
	public function tables_extra_transferData() {
		$res = array();
		foreach ($this->extraTablesDef as $idx => $data) {
			$res[] = $this->tables_transferData($idx);
		}
		return $res;
	}

	/**
	 * Let each table objects process its data. As the main-table object is the first in the list of
	 * table objects it will get processed at the beginning.
	 *
	 * @return	array		An array with indexes for all initialized table objects
	 */
	public function tables_process($subController = false) {
		if (!$subController && $this->isSearch) {
			$subQueryController = t3lib_div::makeInstance('tx_kbdisplay_queryController');
			$subQueryController->init($this->parentObj, $this->rootObj);
			$subQueryController->parseFlexform();
			$subQueryController->tables_main_transferData();
			$subQueryController->tables_extra_transferData();
			$subQueryController->tables_process(true);
			// Let the query get executed
			$subQueryController->queryExecute(false, true);
			$subQueryController->fetchResult(false, true);
			$uidRows = $subQueryController->getResult(true);
			if (is_array($uidRows)) {
				$uidRows = array_map('array_pop', $uidRows);
				$this->mainTablesDef['list_criteria_section'][] = $this->getCriteria_singleView(implode(',', $uidRows), $idx);
				$this->tableObjects[0]->set_flexData($this->mainTablesDef);
			}
		}
		foreach ($this->tableObjects as $idx => &$tableObj) {
			$this->tableObjects[$idx]->setup($idx, $subController && $this->isSearch);
		}
		foreach ($this->tableObjects as $idx => &$tableObj) {
			$this->tableObjects[$idx]->process($subController && $this->isSearch);
		}
	}

	/**
	 * Returns the name of the table for passed table index
	 *
	 * @param		integer		The index of the table for which to return the table name.
	 * @return	string		The name of the table
	 */
	public function get_tableName($idx) {
		if (!is_object($this->tableObjects[$idx])) {
			print_r(t3lib_div::debug_trail());
		}
		return $this->tableObjects[$idx]->get_tableName();
	}

	/**
	 * Returns the result-name of the table specified by passed table index
	 *
	 * @param	integer/false	The index of the table for which to return the result-name.
	 * @return	string		The result-name for this table
	 */
	public function get_resultName($idx = false) {
		if (!is_object($this->tableObjects[$idx])) {
			print_r(t3lib_div::debug_trail());
		}
		return $this->tableObjects[$idx]->get_resultName();
	}

	/**
	 * Returns an array containing the indexes of all queried/joined tables
	 *
	 * @return	array			An array containing the indexes
	 */
	public function get_tableIndexes() {
		return array_keys($this->tableObjects);
	}

	/**
	 * Let the queryGenerator object instance prepare the query and execute it.
	 *
	 * @return	void
	 */
	public function queryExecute($resultCount = false, $onlyUids = false) {
		// QUERY GENERATOR:
		// Let the query generator prepare the query
		$this->queryGenerator->queryPrepare(false, $resultCount, $onlyUids);
		// Let the query get executed
		if (!$this->isSearch || $this->searchWords) {
			$this->queryGenerator->queryExecute();
		}
	}

	/**
	 * Use the queryFetcher object instance to retrieve all result rows, then invoke the queryGenerator methods of the sub_queryGenerators to fetch sub-records
	 *
	 * @return	void
	 */
	public function fetchResult($makeSubQueries = true, $clearFetcher = false) {
		// Let the query fetcher object instance retrieve all result rows from database
		if (!$this->isSearch || $this->searchWords) {
			$this->queryFetcher->fetchResult($clearFetcher);
			if ($makeSubQueries && is_array($this->sub_queryGenerators) && count($this->sub_queryGenerators)) {
				$tmp_resultData = &$this->queryFetcher->get_resultData();
				if (is_array($tmp_resultData)) {
					foreach ($this->sub_queryGenerators as $queryIdx => &$queryGenerator) {
						foreach ($tmp_resultData as $resIdx => $resultRow) {
								// Create cloned objects for sub-queries
							$tmp_queryGenerator = clone($queryGenerator);
							$tmp_queryFetcher = t3lib_div::makeInstance('tx_kbdisplay_queryFetcher');
							$tmp_queryFetcher->init($tmp_queryGenerator, $this);
								// Prepare and execute sub-query
							$tmp_queryGenerator->queryPrepare($resultRow);
							$tmp_result = $tmp_queryGenerator->queryExecute();
							$tmp_queryFetcher->fetchResult();
							$this->queryFetcher->insertSubResult($resIdx, $tmp_queryFetcher);
						}
					}
				}
			}
		}
	}

	/**
	 * Let the rowProcess object instance process all fetched rows
	 *
	 * @return	void
	 */
	public function transformResult() {
		// Let the row processor object instance handle all result transformations
		$this->resultData = $this->rowProcessor->transformResult();
	}

	/**
	 * Return the fetched, merged, processed, and - treatened in any other thinkable way - records
	 *
	 * @return	array			The fully processed result rows and their probable sub-records
	 */
	public function getResult($unprocessed = false) {
		if ($unprocessed) {
			return $this->queryFetcher->get_resultData();
		} else {
			return $this->resultData;
		}
	}

	/**
	 * Initialize the queryGenerator object instance
	 *
	 * @return	void
	 */
	public function initObject_queryGenerator($idx = 0) {
		$queryGenerator = t3lib_div::makeInstance('tx_kbdisplay_queryGenerator');
		$queryGenerator->init($this, $this->rootObj);
		if ($idx) {
			$this->sub_queryGenerators[$idx] = &$queryGenerator;
		} else {
			$this->queryGenerator = &$queryGenerator;
		}
	}

	/**
	 * Return the "queryGenerator" object instance
	 *
	 * @return	object		The current objects instance of "queryGenerator"
	 */
	public function &get_queryGenerator($idx = 0) {
		if ($idx) {
			if (!$this->sub_queryGenerators[$idx]) {
				$this->initObject_queryGenerator($idx);
			}
			return $this->sub_queryGenerators[$idx];
		} else {
			return $this->queryGenerator;
		}
	}

	/**
	 * Initialize the queryFetcher object instance
	 *
	 * @return	void
	 */
	public function initObject_queryFetcher() {
		$this->queryFetcher = t3lib_div::makeInstance('tx_kbdisplay_queryFetcher');
		$this->queryFetcher->init($this, $this->rootObj);
	}

	/**
	 * Return the "queryFetcher" object instance
	 *
	 * @return	object		The current objects instance of "queryGenerator"
	 */
	public function get_queryFetcher() {
		return $this->queryFetcher;
	}

	/**
	 * Initialize the rowProcessor object instance
	 *
	 * @return	void
	 */
	public function initObject_rowProcessor() {
		$this->rowProcessor = t3lib_div::makeInstance('tx_kbdisplay_rowProcessor');
		$this->rowProcessor->init($this, $this->rootObj);
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryController.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryController.php']);
}

?>
