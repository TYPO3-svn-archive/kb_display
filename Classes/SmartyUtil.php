<?php
namespace thinkopen_at\kbDisplay;
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
 * Class for additional smarty stuff
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	kb_display
 */
class SmartyUtil {

	public function getInstance() {
		$smarty = \tx_smarty::smarty();
		$modifiers = GeneralUtility::makeInstance('thinkopen_at\kbDisplay\Hooks\Smarty\Modifiers');
		foreach ($modifiers->getPluginMethods() as $name => $method) {
			$smarty->register_modifier($name, array('thinkopen_at\kbDisplay\Hooks\Smarty\Modifiers', $method));
		}
		return $smarty;
	}

}
