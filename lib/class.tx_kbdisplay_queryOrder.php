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
 * Class handling the ordering/sorting elements of the BE plugin flexform
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbt3tris
 */
class tx_kbdisplay_queryOrder {
	private $parentObj = null;
	private $rootObj = null;
	private $table = null;
	private $tableIndex = null;
	private $ordering_flexFormData = array();
	private $ordering = array();

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
	 * Set ordering flexform data
	 *
	 * @param	array		The flexform data of criterias set
	 * @return	void
	 */
	public function set_ordering($orderingData) {
		$this->ordering_flexFormData = $orderingData;
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
	 * Parse ordering flexform data
	 *
	 * @return	integer		The number of order statements
	 */
	public function parse_ordering() {
		if (is_array($this->ordering_flexFormData)) {
			foreach ($this->ordering_flexFormData as $ordering) {
				$order = $this->parse_order($ordering);
				if ($order) {
					$this->ordering[] = $order;
				}
			}
		}
		return count($this->ordering);
	}

	/**
	 * Parses an flexform order definition into a order-definition array suitable for a query object instance
	 *
	 * @param	array		A parsed criteria flexform definition
	 * @return	array		The where definition for a criteria
	 */
	private function parse_order($order) {
		$field = $order['field_sort_field'];
		list($field, $tableIdx) = explode('__', $field);
		$tableIdx = intval($tableIdx);
		$table = $this->parentObj->get_tableName($tableIdx);

		$orderData = false;
		if ($tableIdx === $this->tableIndex) {
			if ($file = t3lib_div::getFileAbsFileName($order['field_sort_custom'])) {
				$order['orderField']['field'] = $field;
				$order['orderField']['table'] = $table;
				$order['orderField']['index'] = $tableIdx;
				$order['orderField']['current']['table'] = $this->table;
				$order['orderField']['current']['index'] = $this->tableIndex;
				$order['fe_user'] = $GLOBALS['TSFE']->loginUser ? $GLOBALS['TSFE']->fe_user->user : false;
				$order['orderDirection'] = $order['field_sort_direction'];
				$smarty = $this->rootObj->get_smartyClone();
				$smarty->assign('order', $order);
				$smarty->setSmartyVar('template_dir', dirname($file));
				$orderXML = $smarty->display($file, md5($file));
				$orderData = t3lib_div::xml2array($orderXML);
				if (!is_array($orderData)) {
					die('Invalid order XML for field "'.$field.'"!');
				}
				if (!$orderData['field']) {
					die('Invalid order XML for field "'.$field.'". Array key "field" missing!');
				}
				if (!$orderData['direction']) {
					die('Invalid order XML for field "'.$field.'". Array key "direction" missing!');
				}
			} else {
				$orderData = array(
					'field' => '`'.$table.'__'.$tableIdx.'`.`'.$field.'`',
					'direction' => $order['field_sort_direction'],
				);
			}
		}
		return $orderData;
	}

	/**
	 * Sets the parsed criterias in the queryGenerator object instance using passed combination operator
	 *
	 * @param	string		The combination operator (AND/OR) for the parsed criterias
	 * @return	void
	 */
	public function setQuery_order() {
		$this->queryGenerator->set_orders($this->ordering, $this->tableIndex);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryOrder.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/lib/class.tx_kbdisplay_queryOrder.php']);
}

?>
