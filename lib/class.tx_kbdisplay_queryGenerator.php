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
 * Class for generating and executing the query puzzled together
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	kb_display
 */
class tx_kbdisplay_queryGenerator {
	private $parentObj = null;
	private $rootObj = null;

	private $tables = array();
	private $fields = array();
	private $whereParts = array();
	private $orderParts = array();
	private $limit = -1;

	private $query = array(
		'SELECT' => '',
		'FROM' => '',
		'WHERE' => '',
		'GROUPBY' => '',
		'ORDERBY' => '',
		'LIMIT' => '',
	);


	/**
	 * Initialize the object instance
	 *
	 * @param	object		A pointer to the parent object instance (The FE-plugin)
	 * @return	void
	 */
	public function init(&$parentObj, &$rootObj) {
		$this->parentObj = &$parentObj;
		$this->rootObj = &$rootObj;
	}

	private function initQueryArray() {
		$this->query = array(
			'SELECT' => '',
			'FROM' => '',
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => '',
		);
	}

	/**
	 * Sets a table of the query. Either the main table or an additional table
	 *
	 * @param		string			The name of the table being set
	 * @param		string			The index of the table
	 * @param		string			The result name of the table
	 * @param		string			How to join the table. Or false if the main tables is being set.
	 * @return	integer			The index of the created table
	 */
	public function set_table($table, $idx, $resultName = '', $joinType = false) {
// echo "set_table: $idx<br />\n";
//		$idx = count($this->tables);
		$asName = $table.'__'.intval($idx);
		$asSQL = $table.' AS '.$asName;
		if ($joinType) {
			switch ($joinType) {
				case 'join':
					$joinLine = ' JOIN ';
				break;
				case 'leftjoin':
					$joinLine = ' LEFT JOIN ';
				break;
				default:
					// TODO: Error handling and logging
					die('Invalid join type !');
				break;
			}
			$joinLine .= $asSQL;
		} else {
			$joinLine = $asSQL;
		}
		if ($this->tables[$idx]) {
// echo "bla $asSQL<br />\n";
// echo $this->tables[$idx]['asSQL']."<br />\n";
//echo $this->tables[$idx]['asSQL']."\n";
//echo $asSQL;
//exit();
			if (strcmp($this->tables[$idx]['asSQL'], $asSQL)) {
				$this->tables[$idx]['joinLine'] .= ' NATURAL JOIN '.$asSQL;
			}
		} else {
			$this->tables[$idx] = array(
				'table' => $table,
				'index' => $idx,
				'joinType' => $joinType,
				'asSQL' => $asSQL,
				'asName' => $asName,
				'resultName' => $resultName,
				'joinLine' => $joinLine,
			);
		}
		return $idx;
	}

	/**
	 * Sets passed criteria to one of the interal query building arrays
	 *
	 * @param	array		The list of criterias to set
	 * @param	string		How the criterias shall get joined (AND/OR)
	 * @return	void
	 */
	public function set_criteria($criterias, $connector) {
		$this->wherePartConnector = strtoupper($connector);
		foreach ($criterias as $criteria) {
			$idx = count($this->whereParts);
			if (strlen($subconnector = $criteria['connector']) && is_array($criteria['criterias'])) {
				$subResult = array();
				foreach ($criteria['criterias'] as $subcriteria) {
					$where = '('.trim($subcriteria['operand1'].' '.$subcriteria['operator'].' '.$subcriteria['operand2']).')';
					$subResult[] = $where;
				}
				$where = '('.implode(' '.$subconnector.' ', $subResult).')';
				$this->whereParts[$idx] = array(
					'criteriaArray' => $criteria,
					'whereSQL' => $where,
				);
			} else {
				$where = '('.trim($criteria['operand1'].' '.$criteria['operator'].' '.$criteria['operand2']).')';
				$this->whereParts[$idx] = array(
					'criteriaArray' => $criteria,
					'whereSQL' => $where,
				);
			}
		}
	}

	/**
	 * Sets an "ON"-clause for a joined table
	 *
	 * @param	array		The list of criterias to set in the ON clause
	 * @param	string		How the criterias in the ON clause shall get joined (AND/OR)
	 * @param	integer		The index of the joined table
	 * @param	array		Information about how result rows should get merged/combined
	 * @return	void
	 */
	public function set_onClause($criterias, $connector, $tableIdx, $joinInfo) {
		foreach ($criterias as $criteria) {
			$idx = count($this->tables[$tableIdx]['onClause']);
			$clauseSQL= '('.$criteria['operand1'].' '.$criteria['operator'].' '.$criteria['operand2'].')';
			$this->tables[$tableIdx]['onClauseConnector'] = $connector;
			$this->tables[$tableIdx]['joinInfo'] = $joinInfo;
			$this->tables[$tableIdx]['onClause'][$idx] = array(
				'criteriaArray' => $criteria,
				'onClauseSQL' => $clauseSQL,
			);
		}
	}

	/**
	 * Sets the fields to select from a table
	 *
	 * @param	array		An array containing all fields to select
	 * @param	integer		The index of the table for which those fields are going to get selected
	 * @return	void
	 */
	public function set_fields($fields, $tableIdx) {
		foreach ($fields as $field) {
			$idx = count($this->fields);
			$tableName = $this->tables[$tableIdx]['asName'];
			$nameReal = $tableName.'.'.$field;
			$nameSQL = $field.'__'.$tableIdx;
			$asSQL = $nameReal.' AS '.$nameSQL;
			$this->fields[$idx] = array(
				'tableIndex' => $tableIdx,
				'nameReal' => $nameReal,
				'nameSQL' => $nameSQL,
				'asSQL' => $asSQL,
			);
		}
	}

	/**
	 * Sets the ordering of the queried rows
	 *
	 * @param	array		An array containing the fields for ordering
	 * @param	integer		The index of the table of which the passed fields are???
	 * @return	void
	 */
	public function set_orders($fields, $tableIdx) {
		foreach ($fields as $order) {
			$idx = count($this->orderParts);
			$orderSQL = $order['field'].' '.$order['direction'];
			$this->orderParts[$idx] = array(
				'orderArray' => $order,
				'orderSQL' => $orderSQL,
			);
		}
	}

	/**
	 * Sets the maximum number of rows to select
	 *
	 * @param	integer		The maximum numer of rows to select (0 for all)
	 * @return	void
	 */
	public function set_limit($limit) {
		$this->limit = intval($limit);
	}

	/**
	 * Sets the row of set for the query
	 *
	 * @param	integer		The maximum numer of rows to select (0 for all)
	 * @return	void
	 */
	public function set_offset($offset) {
		$this->offset = intval($offset);
	}

	/**
	 * Prepares set fields for the SQL query
	 *
	 * @return	void
	 */
	private function prepare_fields($resultCount = false, $onlyUids = false) {
		$parts = array();
		if ($resultCount) {
			$this->query['SELECT'] = 'count(*) AS cnt';
		} elseif ($onlyUids) {
			$mainTable = $this->tables[0];
			$this->query['SELECT'] = 'DISTINCT ('.$mainTable['asName'].'.uid) AS uid';
		} else {
			foreach ($this->fields as $field) {
				$parts[] = $field['asSQL'];
			}
			$this->query['SELECT'] = implode(', ', $parts);
		}
	}

	/**
	 * Prepares set tables and joined tables for the SQL query
	 *
	 * @return	void
	 */
	private function prepare_from() {
		$parts = array();
		foreach ($this->tables as $table) {
			$part = $table['joinLine'];
			if (is_array($table['onClause']) && count($table['onClause'])) {
				// TODO: Use "prepare_where" to create the onClauseSQL string
				$part .= ' ON ';
				$cnt = 0;
				foreach ($table['onClause'] as $clause) {
					if ($cnt) {
						$part .= ' '.$table['onClauseConnector'].' ';
					}
					$part .= $clause['onClauseSQL'];
					$cnt++;
				}
			}
			$parts[] = $part;
		}
// print_r($parts);
		$this->query['FROM'] = implode('', $parts);
	}

	/**
	 * Prepares the set where part for the SQL query
	 *
	 * @return	void
	 */
	private function prepare_where() {
		$parts = array();
		if (is_array($this->whereParts) && count($this->whereParts)) {
			foreach ($this->whereParts as $wherePart) {
				$parts[] = $wherePart['whereSQL'];
			}
		}
		$this->query['WHERE'] = implode(' '.$this->wherePartConnector.' ', $parts);
	}

	/**
	 * Prepares set sorting criteria for the SQL query
	 *
	 * @return	void
	 */
	private function prepare_order() {
		$parts = array();	
		foreach ($this->orderParts as $order) {
			$parts[] = $order['orderSQL'];
		}
		$this->query['ORDERBY'] = implode(', ', $parts);
	}

	/**
	 * Add the set row limit to the SQL query
	 *
	 * @return	void
	 */
	private function prepare_limit() {
		if ($this->offset) {
			if ($this->limit >= 0) {
				$this->query['LIMIT'] = $this->offset.', '.$this->limit;
			} else {
				$this->query['LIMIT'] = $this->offset.', 2000000000';
			}
		} else {
			if ($this->limit >=0) {
				$this->query['LIMIT'] = $this->limit;
			}
		}
/*
		if ($this->offset && $this->limit) {
			$this->query['LIMIT'] = $this->offset.', '.$this->limit;
		} elseif ($this->limit) {
			$this->query['LIMIT'] = $this->limit;
		} elseif ($this->offset) {
			// Mysql manual:
			// To retrieve all rows from a certain offset up to the end of the result set, you can use some large number for the second parameter.
			$this->query['LIMIT'] = $this->offset.', 2000000000';
		}
*/
	}

	/**
	 * Calls all necessary class methods to prepare the SQL query array
	 *
	 * @return	void
	 */
	public function queryPrepare($replaceData = false, $resultCount = false, $onlyUids = false) {
		$this->initQueryArray();
		$this->prepare_fields($resultCount, $onlyUids);
		$this->prepare_from();
		$this->prepare_where();
		$this->prepare_order();
		if (!($resultCount || $onlyUids)) {
			$this->prepare_limit();
		}
		if ($replaceData) {
			$this->replace_where($replaceData);
		}
	}

	/**
	 * Replaces parts of the "where" criteria with supplied parameters
	 *
	 * @param	array		An array of values to replace in the where part of the query
	 * @return	void
	 */
	private function replace_where($replaceData) {
		$replaceKeys = array();
		$replaceValues = array();
		foreach ($replaceData as $key => $replaceValue) {
			list($field, $tableIdx) = explode('__', $key);
			if (is_array($replaceValue)) {
				continue;
			}
			$tableName = $this->parentObj->get_tableName($tableIdx);
			$replaceKey = '`'.$tableName.'__'.$tableIdx.'`.`'.$field.'`';
			$replaceKeys[] = $replaceKey;
			$replaceValues[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($replaceValue, $tableName);
		}
//print_r($replaceKeys);
//print_r($replaceValues);
		$this->query['WHERE'] = str_replace($replaceKeys, $replaceValues, $this->query['WHERE']);
	}

	/**
	 * Executes the generated query
	 *
	 * @return	boolean		Wheter the SELECT query was successfull or not (meaning: returned a result resource)
	 */
	public function queryExecute() {
		if ($this->result) {
			$GLOBALS['TYPO3_DB']->sql_free_result($this->result);
			$this->result = false;
		}
//$GLOBALS['TYPO3_DB']->debugOutput = true;
//$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
//print_r($this->query);
//exit();
		$this->result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($this->query);
//echo "\n".$GLOBALS['TYPO3_DB']->debug_lastBuiltQuery."\n";
//exit();
		return $this->result?true:false;
	}

	/**
	 * Returns the result resource of the executed query
	 *
	 * @return	resource	The result resource of the executed query
	 */
	public function get_queryResult() {
		return $this->result;
	}

	/**
	 * Returns joining information about how to combine/merge result rows
	 *
	 * @param	integer		The index of the table for which to return joining information
	 * @return	array		The requested joining information
	 */
	public function get_tableJoinInfo($tableIdx) {
		return $this->tables[$tableIdx]['joinInfo'];
	}

	/**
	 * Returns a pointer to this object (the queryGenerator)
	 *
	 * @return	object		A reference to the queryGenerator object instance
	 */
	public function &get_queryGenerator() {
		return $this;
	}

	/**
	 * Using this method you can retrieve the table array for the main queried table
	 *
	 * @return	array		Table array for queried table
	 */
	public function get_mainTable() {
		$keys = array_keys($this->tables);
		if (count($keys)) {
			$idx = array_shift($keys);
			return $this->tables[$idx];
		} else {
			return false;
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryGenerator.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryGenerator.php']);
}

?>
