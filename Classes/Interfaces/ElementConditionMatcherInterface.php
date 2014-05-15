<?php
namespace thinkopen_at\kbDisplay\Interfaces;

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
 * This interface has to get implemented by classes whishing to be used
 * as "ElementConditionMatcher" custom matcher objects.
 */
interface ElementConditionMatcherInterface {

	/**
	 * Constructor for a custom element matcher.
	 *
	 * @param \TYPO3\CMS\Backend\Form\ElementConditionMatcher $parentObject: The referenceo to the object from which this class was instanciated
	 */
	public function __construct(\TYPO3\CMS\Backend\Form\ElementConditionMatcher $parentObject);

	/**
	 * This method has to perform the matching of the passed condition
	 *
	 * @param string $condition: The condition which should get matched
	 * @return boolean Has to return true if the condition evaluated to true
	 */
	public function matchCondition($condition);

}

