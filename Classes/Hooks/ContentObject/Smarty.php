<?php
namespace thinkopen_at\kbDisplay\Hooks\ContentObject;
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
use \thinkopen_at\kbDisplay\SmartyUtil;;

/**
 * Content object "SMARTY" implementation
 *
 * @author Bernhard Kraft <kraftb@think-open.at>
 * @package TYPO3
 * @subpackage kb_display
 */
class Smarty {
	var $smarty_cache = false;
	var $smarty_compileDir= 'typo3temp/smarty_compile';
	var $smarty_cacheDir= 'typo3temp/smarty_cache';


	/*
	 * Renders the SMARTY cObject
	 *
	 * @param string $name: Should be "SMARTY"
	 * @param array $conf: The TypoScript configuration for this content object
	 * @param string $TSkey: Path to the currently rendered TS object
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObj: A pointer to the parent content object renderer
	 */
	public function cObjGetSingleExt($name, array $conf, $TSkey, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer &$parentObj) {
		$this->smarty = SmartyUtil::getInstance();
		$this->smarty->setSmartyVar('caching', $this->cacheSmarty);
		$this->smarty->setSmartyVar('compile_dir', $this->smarty_compileDir);
		$this->smarty->setSmartyVar('cache_dir', $this->smarty_cacheDir);
		$content = '';

		$template = $parentObj->stdWrap($conf['template'], $conf['template.']);
		$templateFile = GeneralUtility::getFileAbsFileName($template);
		if (file_exists($templateFile) && is_readable($templateFile)) {
			if ($conf['setData']) {
				$this->smarty->assign('data', $parentObj->data);
			}
			$templateDir = dirname($templateFile);
			$this->smarty->setTemplateDir($templateDir);
			$content = $this->smarty->display(basename($templateFile), '', md5($templateDir));
		}
		return $content;
	}

}
