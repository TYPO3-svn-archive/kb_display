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


// TODO: Probably implement these routines in a way saving memory.
// So only one row gets fetched then directly processed, all previous
// information unset. And the final row data appended to the result
// array in the root Object.

/**
 * Class for fetching and processing the query results
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	kb_display
 */
class tx_kbdisplay_queryFetcher {
	private $parentObj = NULL;
	private $rootObj = NULL;
	private $queryGenerator = NULL;

	private $queryResult = NULL;
	private $resultData = NULL;
	private $resultCount = 0;

	/**
	 * Initialize the object instance
	 *
	 * @param	object		A pointer to the parent object instance (The FE-plugin)
	 * @return	void
	 */
	public function init(&$parentObj, &$rootObj) {
		$this->parentObj = &$parentObj;
		$this->rootObj = &$rootObj;
		$this->queryGenerator = &$this->parentObj->get_queryGenerator();
	}

	/**
	 * Clears the resultData array
	 *
	 * @return	void
	 */
	public function clear() {
		$this->resultData = array();
	}




	/*************************
	 *
	 * Query methods
	 *
	 * Those methods are responsible for fetching the content (rows) of the query 
	 * having been executed. The fetched result rows will get piped through the
	 * transformation class/object for letting the transformations happen defined
	 * via TypoScript
	 *
	 *************************/

	/**
	 * This method fetches all result rows
	 *
	 * @return	void
	 */
	function fetchResult($clearResult = false) {
		if ($clearResult) {
			$this->clear();
		}
		$this->queryResult = $this->queryGenerator->get_queryResult();
		while ($row = $this->fetchRow()) {
			$this->resultData[] = $row;
		}
		if ($debug) {
			print_r($this->resultData);
			exit();
		}
		$this->resultCount = count($this->resultData);
	}

	/**
	 * This method fetches a single result row and returns it
	 *
	 * @return	mixed			Either the fetched result row array, or false in case of error
	 */
	function fetchRow() {
		// TODO: Proper error handling
		if ($this->queryResult) {
			return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->queryResult);
		} else {
//			$this->addError('ERROR', 'No query result available !');
			die('ERROR: No query result available !');
			return false;
		}
	}

	/**
	 * This method returns the result data array (can be quite large)
	 *
	 * @return	array		The result data array
	 */
	public function &get_resultData() {
		return $this->resultData;
	}

	/**
	 * Returns a reference to the queryGenerator object instance used in this class
	 *
	 * @return	object		A reference to the queryGenerator instance used
	 */
	public function &get_queryGenerator() {
		return $this->queryGenerator;
	}

	/**
	 * Inserts sub result-rows into a array key of the the current result rows
	 *
	 * @param	index		The index of the row into which the sub-result rows shall get inserted
	 * @param	object		A reference to the queryFetcher object instance from which to retrieve the sub-rows
	 * @return	void
	 */
	public function insertSubResult($resultIdx, &$fetcherObj) {
		if (is_array($this->resultData[$resultIdx])) {
			$queryGenerator = &$fetcherObj->get_queryGenerator();
			$subTable = $queryGenerator->get_mainTable();
			if ($subTable) {
				$resultName = $subTable['resultName']?$subTable['resultName']:$subTable['asName'];
				$resultData = $fetcherObj->get_resultData();
				if (!is_array($resultData)) {
					$resultData = array();
				}
				$this->resultData[$resultIdx][$resultName] = $resultData;
			}
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryFetcher.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryFetcher.php']);
}

?>
