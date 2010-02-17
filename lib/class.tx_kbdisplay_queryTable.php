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


require_once(PATH_kb_display.'lib/class.tx_kbdisplay_queryCriteria.php');
require_once(PATH_kb_display.'lib/class.tx_kbdisplay_queryOrder.php');

/**
 * Class for handling of each flexform table definition
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbt3tris
 */
class tx_kbdisplay_queryTable {
	private $parentObj = null;
	private $rootObj = null;
	private $queryGenerator = null;
	private $criteriaObj = null;
	private $table_flexFormData = array();
	private $enableFields = array();

	public $table = null;
	private $tableIndex = 0;
	private $joinType = null;
	private $criteriaConnector = null;
	private $criteriaArray = array();
	private $orderArray = array();

	private $filtersConnector = null;
	private $searchConnector = null;


	/**
	 * Initialize the object instance
	 *
	 * @param	object		A pointer to the parent object instance (The FE-plugin)
	 * @return	void
	 */
	public function init(&$parentObj, &$rootObj) {
		$this->parentObj = &$parentObj;
		$this->rootObj = &$rootObj;
		$this->criteriaObj = t3lib_div::makeInstance('tx_kbdisplay_queryCriteria');
		$this->filtersObj = t3lib_div::makeInstance('tx_kbdisplay_queryCriteria');
		$this->searchObj = t3lib_div::makeInstance('tx_kbdisplay_queryCriteria');
		$this->orderObj = t3lib_div::makeInstance('tx_kbdisplay_queryOrder');
	}

	/**
	 * Set the flexform data containing the definition for the table and it's criterias
	 *
	 * @param		array				The parsed flexForm data for this table
	 * @return	void
	 */
	public function set_flexData($data) {
		$this->table_flexFormData = $data;
	}

	/**
	 * Process the data from the flexForm
	 *
	 * @return	void
	 */
	public function setup($index, $isSearch = false) {
		$this->table = $this->table_flexFormData['field_table'];
		t3lib_div::loadTCA($this->table);
		$this->tableIndex = $index;

			// Set the "this->enableFields" array to contain proper WHERE strings
		if (is_array($ctrl = $GLOBALS['TCA'][$this->table]['ctrl'])) {
			if ($this->table_flexFormData['check_enableDefault']) {
				if ($ctrl['delete'])	{
					$this->enableFields['default'] .= ($this->enableFields['default']?' AND ':'').$this->table.'__'.$this->tableIndex.'.'.$ctrl['delete'].'=0';
				}
					// TODO: What is the parentObject? Take care of "versioningPreview"
				if ($ctrl['versioningWS'] && !$this->parentObj->versioningPreview) {
					$this->enableFields['default'] .= ($this->enableFields['default']?' AND ':'').$this->table.'__'.$this->tableIndex.'.t3ver_state<=0';
				}
				if (is_array($ctrl['enablecolumns'])) {
					if ($ctrl['enablecolumns']['disabled'] && !$this->parentObj->show_hidden) {
						$this->enableFields['default'] .= ($this->enableFields['default']?' AND ':'').$this->table.'__'.$this->tableIndex.'.'.$ctrl['enablecolumns']['disabled'].'=0';
					}
				}
			}
			if ($this->table_flexFormData['check_enableTime']) {
				if ($ctrl['enablecolumns']['starttime']) {
					$this->enableFields['time'] .= ($this->enableFields['time']?' AND ':'').$this->table.'__'.$this->tableIndex.'.'.$ctrl['enablecolumns']['starttime'].'<='.$GLOBALS['SIM_ACCESS_TIME'];
				}
				if ($ctrl['enablecolumns']['endtime']) {
					$this->enableFields['time'] .= ($this->enableFields['time']?' AND ':'').'('.$this->table.'__'.$this->tableIndex.'.'.$ctrl['enablecolumns']['endtime'].'=0 OR '.$this->table.'__'.$this->tableIndex.'.'.$ctrl['enablecolumns']['endtime'].'>'.$GLOBALS['SIM_ACCESS_TIME'].')';
				}
			}
			if ($this->table_flexFormData['check_enableAccess']) {
				if ($ctrl['enablecolumns']['fe_group']) {
					$field = $this->table.'__'.$this->tableIndex.'.'.$ctrl['enablecolumns']['fe_group'];
					$this->enableFields['access'] = $this->getMultipleGroupsWhereClause($field, $this->table);
				}
			}
		}

		$this->resultName = $this->table_flexFormData['field_resultname'];
		$this->joinType = $this->table_flexFormData['field_jointype'];
		$this->isSearch = $isSearch;
		if ($index && $isSearch) {
			if ($this->joinType == 'query') {
				$this->joinType = 'leftjoin';
			}
		}
		$this->combineResult = $this->table_flexFormData['field_combineResult'];
		$this->criteriaConnector = $this->table_flexFormData['field_criteriaConnector'];
		$this->filtersConnector = $this->table_flexFormData['field_filtersConnector'];
//		$this->searchConnector = $this->table_flexFormData['field_filtersConnector'];
		// TODO: Retrieve from GET/POST vars
		$this->searchConnector = 'AND';
		$this->searchCase = false;
		$this->criteriaArray = $this->table_flexFormData['list_criteria_section'];
		$this->filtersArray = $this->table_flexFormData['list_filters_section'];
		$this->searchArray = $this->table_flexFormData['field_search_fields'];
		$this->orderArray = $this->table_flexFormData['list_ordering_section'];

		$this->queryGenerator = &$this->get_queryGenerator();

		$this->criteriaObj->init($this, $this->rootObj);
		$this->filtersObj->init($this, $this->rootObj, 'filters');
		$this->searchObj->init($this, $this->rootObj);

		$this->orderObj->init($this, $this->rootObj);
	}

	/**
	 * Creating where-clause for checking group access to elements in enableFields function
	 * copied from: t3lib/class.t3lib_page.php
	 *
	 * @param	string		Field with group list
	 * @param	string		Table name
	 * @return	string		AND sql-clause
	 * @see enableFields()
	 */
	protected function getMultipleGroupsWhereClause($field, $table) {
		$memberGroups = t3lib_div::intExplode(',',$GLOBALS['TSFE']->gr_list);
		$orChecks=array();
		$orChecks[]=$field.'=\'\'';	// If the field is empty, then OK
		$orChecks[]=$field.' IS NULL';	// If the field is NULL, then OK
		$orChecks[]=$field.'=\'0\'';	// If the field contsains zero, then OK

		foreach($memberGroups as $value)	{
			$orChecks[] = $GLOBALS['TYPO3_DB']->listQuery($field, $value, $table);
		}

		return '('.implode(' OR ',$orChecks).')';
	}

	/**
	 * Process the data from the flexForm
	 *
	 * @return	void
	 */
	public function process($isSearch = false) {
		$this->criteriaObj->set_table($this->table);
		$this->criteriaObj->set_criterias($this->criteriaArray);
		$this->criteriaObj->parse_criterias();

			// Set filters
		$this->filtersObj->set_table($this->table);
		$this->filtersObj->set_criterias($this->filtersArray);
		$this->filtersObj->set_filterValues();
		$this->filtersObj->parse_criterias();

			// Set search
		if ($isSearch) {
			$this->searchObj->set_table($this->table);
			$this->searchObj->set_criterias($this->searchArray);
			$this->searchObj->set_searchWords($this->searchCase);
		}

		if (($this->joinType == 'main') || ($this->joinType == 'query')) {
			$idx = $this->queryGenerator->set_table($this->table, $this->tableIndex, $this->resultName);
			$this->criteriaObj->setQuery_criteria($this->criteriaConnector);
			if ($this->joinType == 'main') {
				$this->filtersObj->set_joinTables($this->joinType);
				$this->filtersObj->setQuery_criteria($this->filtersConnector);
				if ($isSearch) {
					$this->searchObj->setQuery_criteria($this->searchConnector);
				}
			} elseif ($this->joinType == 'query') {
				$this->criteriaObj->set_joinTables($this->joinType);
			}
		} else {
			$this->criteriaObj->set_joinTables();
			$idx = $this->queryGenerator->set_table($this->table, $this->tableIndex, $this->resultName, $this->joinType);
			$combineResults = (($this->joinType==='leftjoin') && ($this->combineResult))?true:false;
			$this->criteriaObj->setQuery_onClause($this->criteriaConnector, $idx, $combineResults);
		}

		$this->orderObj->set_table($this->table);
		$this->orderObj->set_ordering($this->orderArray);
		$this->orderObj->parse_ordering();
		$this->orderObj->setQuery_order($idx);

		$this->queryGenerator->set_enableFields($idx, $this->enableFields);
		$this->getFields();
		$this->queryGenerator->set_fields($this->fields, $idx);
	}

	/**
	 * Returns the array of filter options
	 *
	 * @return	array		An array of filter options
	 */
	public function get_filterOptions() {
		return $this->filtersObj->get_filterOptions();
	}

	/**
	 * Get all fields for the current table
	 *
	 * @return	void
	 */
	private function getFields() {
		$this->fields = array_keys($GLOBALS['TCA'][$this->table]['columns']);
		$this->fields[] = 'uid';
		$this->fields[] = 'pid';
		if ($delete = $GLOBALS['TCA'][$this->table]['ctrl']['delete']) {
			$this->fields[] = $delete;
		}
		if ($crdate = $GLOBALS['TCA'][$this->table]['ctrl']['crdate']) {
			$this->fields[] = $crdate;
		}
		if ($tstamp = $GLOBALS['TCA'][$this->table]['ctrl']['tstamp']) {
			$this->fields[] = $tstamp;
		}
	}

	/**
	 * Returns the name of the table for passed table index
	 *
	 * @param	integer/false	The index of the table for which to return the table name. Leave parameter away if current table name shall get returned.
	 * @return	string		The name of the table
	 */
	public function get_tableName($idx = false) {
		if ($idx===false) {
			return $this->table;
		} else	{
			$table = $this->parentObj->get_tableName($idx);
			return $table;
		}
	}

	/**
	 * Returns the result-name for this table
	 *
	 * @param	integer/false	The index of the table for which to return the result-name. Leave parameter away if current table result-name shall get returned.
	 * @return	string		The result-name for this table
	 */
	public function get_resultName($idx = false) {
		if ($idx===false) {
			return $this->resultName;
		} else {
			$resultName = $this->parentObj->get_resultName($idx);
			return $resultName;
		}
	}

	/**
	 * Returns the index of the current table (the table this object is instanciated for)
	 *
	 * @return	integer		The index of the current table
	 */
	public function get_tableIndex() {
		return $this->tableIndex;
	}

	/*
	 * This method returns parsed search words
	 *
	 *
	 * @return	array		An array of search words
	 */
	public function get_searchWords() {
		return $this->parentObj->get_searchWords();
	}

	/**
	 * Initialize the queryGenerator object instance
	 *
	 * @return	void
	 */
	public function initObject_queryGenerator() {
		$this->queryGenerator = t3lib_div::makeInstance('tx_kbdisplay_queryGenerator');
		$this->queryGenerator->init($this, $this->rootObj);
	}

	/**
	 * Return the "queryGenerator" object instance
	 *
	 * @return	object		The current objects instance of "queryGenerator"
	 */
	public function &get_queryGenerator() {
		$idx = 0;
		if ($this->joinType == 'query') {
			$idx = $this->tableIndex;
		}
		return $this->parentObj->get_queryGenerator($idx);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryTable.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryTable.php']);
}

?>
