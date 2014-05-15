<?php
namespace thinkopen_at\kbDisplay\Hooks;

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


/**
 * This class implements a condition matcher which evaluates the displayCond
 * TCA setting. It is similar to the default "IN" except that it allows to
 * perform an "substr" operation on the field value before being compared.
 */
class FieldElementConditionMatcher
	extends \TYPO3\CMS\Backend\Form\ElementConditionMatcher
	implements \thinkopen_at\kbDisplay\Interfaces\ElementConditionMatcherInterface
	{

	/*
	 * @var \TYPO3\CMS\Backend\Form\ElementConditionMatcher $parentObject: A reference to the ElementConditionMatcher object instance
	 */
	protected $parentObject = NULL;


	/**
	 * Constructor for a custom element matcher.
	 *
	 * @param \TYPO3\CMS\Backend\Form\ElementConditionMatcher $parentObject: The referenceo to the object from which this class was instanciated
	 */
	public function __construct(\TYPO3\CMS\Backend\Form\ElementConditionMatcher $parentObject) {
		$this->parentObject = $parentObject;
	}


	protected function getValue($fieldName) {
		$fieldValue = NULL;
		if ($this->flexformValueKey) {
			if (strpos($fieldName, 'parentRec.') !== FALSE) {
				$fieldNameParts = explode('.', $fieldName, 2);
				$fieldValue = $this->record['parentRec'][$fieldNameParts[1]];
			} else {
				$fieldValue = $this->record[$fieldName][$this->flexformValueKey];
			}
		} else {
			$fieldValue = $this->record[$fieldName];
		}
		return $fieldValue;
	}

	protected function setValue($fieldName, $fieldValue) {
		if ($this->flexformValueKey) {
			if (strpos($fieldName, 'parentRec.') !== FALSE) {
				$fieldNameParts = explode('.', $fieldName, 2);
				$this->record['parentRec'][$fieldNameParts[1]] = $fieldValue;
			} else {
				$this->record[$fieldName][$this->flexformValueKey] = $fieldValue;
			}
		} else {
			$this->record[$fieldName] = $fieldValue;
		}
	}



	/**
	 * This method has to perform the matching of the passed condition
	 *
	 * @param string $condition: The condition which should get matched
	 * @return boolean Has to return true if the condition evaluated to true
	 */
	public function matchCondition($condition) {
		$this->record = $this->parentObject->record;
		$this->flexformValueKey = $this->parentObject->flexformValueKey;
		list($fieldName, $operator, $operand) = explode(':', $condition, 3);
		if (strpos($fieldName, ',') !== false) {
			list($fieldName, $offset, $length) = explode(',', $fieldName, 3);
			$offset = intval($offset);
			$length = intval($length);
			$fieldValue = $this->getValue($fieldName);
			$fieldValue = substr($fieldValue, intval($offset), intval($length));
			$this->setValue($fieldName, $fieldValue);
			$this->parentObject->record = $this->record;
			$condition = $fieldName.':'.$operator.':'.$operand;
		}
		return $this->matchFieldCondition($condition);
	}


}

