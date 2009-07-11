<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Bernhard Kraft <kraftb@think-open.at>
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



class tx_kbdisplay_smarty {
	var $smarty_cache = false;
	var $smarty_compileDir= 'typo3temp/smarty_compile';
	var $smarty_cacheDir= 'typo3temp/smarty_cache';


	public function cObjGetSingleExt($name, $conf, $TSkey, &$parentObj) {
		$this->smarty = tx_smarty::smarty();
		$this->smarty->setSmartyVar('caching', $this->cacheSmarty);
		$this->smarty->setSmartyVar('compile_dir', $this->smarty_compileDir);
		$this->smarty->setSmartyVar('cache_dir', $this->smarty_cacheDir);
		$content = '';

		$template = $parentObj->stdWrap($conf['template'], $conf['template.']);
		$templateFile = t3lib_div::getFileAbsFileName($template);
		if (file_exists($templateFile) && is_readable($templateFile)) {
			if ($conf['setData']) {
				$this->smarty->assign('data', $parentObj->data);
			}
			$templateDir = dirname($templateFile);
			$this->smarty->setSmartyVar('template_dir', $templateDir);
			$content = $this->smarty->display(basename($templateFile), '', md5($templateDir));
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_smarty.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/hooks/class.tx_kbdisplay_smarty.php']);
}

?>
