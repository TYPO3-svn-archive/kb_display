<?php
namespace thinkopen_at\kbDisplay\Query;
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2014 Bernhard Kraft <kraftb@think-open.at>
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
 * Class handling the "group by" selection of the BE plugin flexform to generate the GROUP BY statement for the SQL query
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbdisplay
 */
class GroupBy {
	private $parentObj = null;
	private $rootObj = null;
	private $table = null;
	private $tableIndex = null;
	private $flexFormData_groupBy = array();
	private $parsed_groupBy = array();

	/**
	 * Initialize the object instance
	 *
	 * @param	object		A pointer to the parent object instance (The FE-plugin)
	 * @return	void
	 */
	public function init(&$parentObj, &$rootObj) {
		$this->parentObj = &$parentObj;
		$this->rootObj = &$rootObj;
		$this->queryGenerator = &$parentObj->get_queryGenerator();
	}

	/**
	 * Set "group by" flexform data
	 *
	 * @param	array		The flexform data from the "group by" field (sheet)
	 * @return	void
	 */
	public function set_groupBy($data_groupBy) {
		$this->flexFormData_groupBy = $data_groupBy;
	}

	/**
	 * Sets the table which is being processed by the grouping object instance
	 *
	 * @param		string		The table being processed
	 * @return	void
	 */
	public function set_table($table) {
		$this->table = $table;
		$this->tableIndex = $this->parentObj->get_tableIndex();
	}

	/**
	 * Parse "group by" flexform data
	 *
	 * @return	integer		The number of order statements
	 */
	public function parse_groupBy() {
		if (is_string($this->flexFormData_groupBy)) {
				// groupBy content is just a comma separated list of fields. For now "trimExplode" will do the job.
				// If more complex "group by" handling is required sometime use the "orderBy" class as an example
				// and create a flexform section instead of a simple field of TCA type "select".
			$fields_groupBy = GeneralUtility::trimExplode(',', $this->flexFormData_groupBy, 1);
			foreach ($fields_groupBy as $field) {
				$item_groupBy = $this->parse_item_groupBy($field);
				if ($item_groupBy) {
					$this->parsed_groupBy[] = $item_groupBy;
				}		
			}
		}
		return count($this->parsed_groupBy);
	}

	/**
	 * Parses an flexform "group by" definition into a "group by" array suitable for a query object instance
	 * There are some commented out code sections right now as "group by" currently only allows a list of fields.
	 * If it ever becomes necessary to have custom "group by" statements then the "group by" field in the
	 * flexform should become a section element. In this section there should be a field named "field_groupBy_custom"
	 * and a field named "field_groupBy_field". Then the commented code in this  method has to get used to parse
	 * the custom group by XML template and return apropriate arrays
	 *
	 * @param	array		A parsed "group by" flexform definition
	 * @return	array		The where definition for a criteria
	 */
	private function parse_item_groupBy($item_groupBy) {
//		$field_groupBy = $item_groupBy['field_groupBy_field'];
		$field_groupBy = $item_groupBy;
		list($field, $tableIdx) = explode('__', $field_groupBy);
		$tableIdx = intval($tableIdx);
		$table = $this->parentObj->get_tableName($tableIdx);

		$parsed_item_groupBy = false;
/*
		if ($file = GeneralUtility::getFileAbsFileName($item_groupBy['field_groupBy_custom'])) {
			$item_groupBy['field_groupBy']['field'] = $field;
			$item_groupBy['field_groupBy']['table'] = $table;
			$item_groupBy['field_groupBy']['index'] = $tableIdx;
			$item_groupBy['field_groupBy']['current']['table'] = $this->table;
			$item_groupBy['field_groupBy']['current']['index'] = $this->tableIndex;
			$item_groupBy['fe_user'] = $GLOBALS['TSFE']->fe_user->user;
			$smarty = $this->rootObj->get_smartyClone();
			$smarty->assign('groupBy', $item_groupBy);
			$smarty->setSmartyVar('template_dir', dirname($file));
			$XML_groupBy = $smarty->display($file, md5($file));
			$parsed_item_groupBy = GeneralUtility::xml2array($XML_groupBy);
			if (!is_array($parsed_item_groupBy)) {
				die('Invalid "group by" XML for field "'.$field.'"!');
			}
			if (!$parsed_item_groupBy['field']) {
				die('Invalid "group by" XML for field "'.$field.'". Array key "field" missing!');
			}
			if (!$parsed_item_groupBy['direction']) {
				die('Invalid "group by" XML for field "'.$field.'". Array key "direction" missing!');
			}
		} else {
*/
			$parsed_item_groupBy = array(
				'field' => '`'.$table.'__'.$tableIdx.'`.`'.$field.'`',
			);
//		}
		return $parsed_item_groupBy;
	}
	
	
	/**
	 * Sets the parsed criterias in the queryGenerator object instance using passed combination operator
	 *
	 * @param	string		The combination operator (AND/OR) for the parsed criterias
	 * @return	void
	 */
	public function setQuery_groupBy() {
		$this->queryGenerator->set_groupBy($this->parsed_groupBy, $this->tableIndex);
	}

}
