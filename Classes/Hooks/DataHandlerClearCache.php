<?php
namespace thinkopen_at\kbDisplay\Hooks;
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2014 Bernhard Kraft <kraftb@think-open.at>
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
 * Hook for clearing FE-Plugin cache files
 *
 * @author Bernhard Kraft <kraftb@think-open.at>
 * @package TYPO3
 * @subpackage kb_display
 */
class DataHandlerClearCache {

	function clearCaches($params, &$parentObject) {
		$path = PATH_site.'typo3temp/kb_display/';
		$files = GeneralUtility::getFilesInDir($path);
		foreach ($files as $file) {
			if ($file !== 'index.html') {
				unlink($path.$file);
			}
		}
	}
}
