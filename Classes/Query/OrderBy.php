<?php
namespace thinkopen_at\kbDisplay\Query;
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2014 Bernhard Kraft <kraftb@think-open.at>
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
 * Class handling the ordering/sorting elements of the BE plugin flexform
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbdisplay
 */
class OrderBy {
	private $parentObj = null;
	private $rootObj = null;
	private $table = null;
	private $tableIndex = null;
	private $flexFormData_orderBy = array();
	private $parsed_orderBy = array();

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
	 * Set "order by" flexform data
	 *
	 * @param	array		The flexform data of criterias set
	 * @return	void
	 */
	public function set_orderBy($config_orderBy) {
		$this->flexFormData_orderBy = $config_orderBy;
	}

	/**
	 * Sets the table which is being processed by the orderBy object instance
	 *
	 * @param		string		The table being processed
	 * @return	void
	 */
	public function set_table($table) {
		$this->table = $table;
		$this->tableIndex = $this->parentObj->get_tableIndex();
	}

	/**
	 * Parse "order by" flexform data
	 *
	 * @return	integer		The number of order statements
	 */
	public function parse_orderBy() {
		if (is_array($this->flexFormData_orderBy)) {
			foreach ($this->flexFormData_orderBy as $flexItem_orderBy) {
				$item_orderBy = $this->parse_item_orderBy($flexItem_orderBy);
				if ($item_orderBy) {
					$this->parsed_orderBy[] = $item_orderBy;
				}
			}
		}
		return count($this->parsed_orderBy);
	}

	/**
	 * Parses an flexform "order by" definition into a order-definition array suitable for a query object instance
	 *
	 * @param	array		A parsed criteria flexform definition
	 * @return	array		The where definition for a criteria
	 */
	private function parse_item_orderBy($flexItem_orderBy) {
		$field = $flexItem_orderBy['field_sort_field'];
		list($field, $tableIdx) = explode('__', $field);
		$tableIdx = intval($tableIdx);
		$table = $this->parentObj->get_tableName($tableIdx);
		$fieldJoinType = $this->parentObj->get_joinType($tableIdx);
		$selfJoinType = $this->parentObj->get_joinType();
		$parsed_item_orderBy = false;

		if (($tableIdx === $this->tableIndex) || (($fieldJoinType === 'leftjoin') && ($selfJoinType !== 'query'))) {
			if ($file = GeneralUtility::getFileAbsFileName($flexItem_orderBy['field_sort_custom'])) {
				$orderBy['field'] = $field;
				$orderBy['table'] = $table;
				$orderBy['index'] = $tableIdx;
				$orderBy['current']['table'] = $this->table;
				$orderBy['current']['index'] = $this->tableIndex;
				$orderBy['fe_user'] = $GLOBALS['TSFE']->loginUser ? $GLOBALS['TSFE']->fe_user->user : false;
				$orderBy['direction'] = $flexItem_orderBy['field_sort_direction'];
				$smarty = $this->rootObj->get_smartyClone();
				$smarty->assign('orderBy', $orderBy);
				$smarty->assign('flexItem', $flexItem_orderBy);
				$smarty->setTemplateDir(dirname($file));
				$XML_orderBy = $smarty->display($file, md5($file));
				$parsed_item_orderBy = GeneralUtility::xml2array($XML_orderBy);
				if (!is_array($parsed_item_orderBy)) {
					die('Invalid order XML for field "'.$field.'"!');
				}
				if (!$parsed_item_orderBy['field']) {
					die('Invalid order XML for field "'.$field.'". Array key "field" missing!');
				}
				if (!$parsed_item_orderBy['direction']) {
					die('Invalid order XML for field "'.$field.'". Array key "direction" missing!');
				}
			} else {
				$parsed_item_orderBy = array(
					'field' => '`'.$table.'__'.$tableIdx.'`.`'.$field.'`',
					'direction' => $flexItem_orderBy['field_sort_direction'],
				);
			}
		}
		return $parsed_item_orderBy;
	}

	/**
	 * Sets the parsed "order by" statement in the queryGenerator object instance
	 *
	 * @param	string		The combination operator (AND/OR) for the parsed criterias
	 * @return	void
	 */
	public function setQuery_orderBy() {
		$this->queryGenerator->set_orderBy($this->parsed_orderBy, $this->tableIndex);
	}

}
