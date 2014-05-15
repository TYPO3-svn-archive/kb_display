<?php
namespace thinkopen_at\kbDisplay\SysObjects;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Bernhard Kraft <kraftb@think-open.at>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
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
 * This class extends the core ElementConditionMatcher Implementation
 * by addint the possibility to call custom compare functions.
 */
class ElementConditionMatcher extends \TYPO3\CMS\Backend\Form\ElementConditionMatcher {

	/**
	 * @var array $customMatchObjects: An associative array of custom match objects (class names)
	 */
	protected static $customMatchObjects = array();

	/**
	 * @var array $customMatchObjects: An associative array of custom match objects (instances)
	 */
	protected $customMatchObjectInstances = array();


	public function __construct() {
		foreach (self::$customMatchObjects as $key => $className) {
			$this->customMatchObjectInstances[$key] = GeneralUtility::makeInstance($className, $this);
		}
	}

	public static function registerMatchObject($type, $className) {
		if (!is_subclass_of($className, 'thinkopen_at\kbDisplay\Interfaces\ElementConditionMatcherInterface')) {
			throw new \UnexpectedValueException('Match object must implement interface \TYPO3\CMS\Backend\Form\ElementConditionMatcherInterface', 1394115087);
		}
		self::$customMatchObjects[$type] = $className;
	}

	/**
	 * Evaluates the provided condition and returns TRUE if the form
	 * element should be displayed.
	 *
	 * The condition string is separated by colons and the first part
	 * indicates what type of evaluation should be performed.
	 *
	 * @param string $displayCondition
	 * @param array $record
	 * @param string $flexformValueKey
	 * @return boolean
	 * @see match()
	 */
	protected function matchSingle($displayCondition, array $record = array(), $flexformValueKey = '') {
		$this->record = $record;
		$this->flexformValueKey = $flexformValueKey;
		$result = FALSE;
		list($matchType, $condition) = explode(':', $displayCondition, 2);
		switch ($matchType) {
			case 'EXT':
				$result = $this->matchExtensionCondition($condition);
				break;
			case 'FIELD':
				$result = $this->matchFieldCondition($condition);
				break;
			case 'HIDE_FOR_NON_ADMINS':
				$result = $this->matchHideForNonAdminsCondition();
				break;
			case 'HIDE_L10N_SIBLINGS':
				$result = $this->matchHideL10nSiblingsCondition($condition);
				break;
			case 'REC':
				$result = $this->matchRecordCondition($condition);
				break;
			case 'VERSION':
				$result = $this->matchVersionCondition($condition);
				break;
			default:
				if (isset($this->customMatchObjectInstances[$matchType])) {
					$result = $this->customMatchObjectInstances[$matchType]->matchCondition($condition);
				}
				break;
		}
		return $result;
	}



}

