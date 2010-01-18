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

require_once(PATH_tslib.'class.tslib_pibase.php');

require_once(PATH_kb_display.'lib/class.tx_kbdisplay_queryController.php');
require_once(PATH_kb_display.'lib/class.tx_kbdisplay_queryGenerator.php');
require_once(PATH_kb_display.'lib/class.tx_kbdisplay_queryFetcher.php');
require_once(PATH_kb_display.'lib/class.tx_kbdisplay_rowProcessor.php');



/**
 * Plugin 'KB Display - Cached' for the 'kb_display' extension.
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 * @package	TYPO3
 * @subpackage	tx_kbt3tris
 *
 * @hook	early_main		Called at the beginning of the main method
 * @hook	late_main		Called at the end of the main method
 * @hook	pre_main_ext		Called before method "main_ext"
 * @hook	post_main_ext		Called after method "main_ext"
 * @hook	early_main_ext		Called at the beginning of the "main_ext" method
 * @hook	pre_initSmart		Called before the smarty object instance gets initialized
 * @hook	post_initSmarty		Called after the smarty object instance has been initialized
 * @hook	pre_setVars		Called before some required variables have been set (see code below)
 * @hook	post_setVars		Called after some required variables have been set (see code below)
 * @hook	pre_getContent		Called before retrieving all content/rows from the database
 * @hook	post_getContent		Called after all content/rows have been retrieved from the database
 * @hook	pre_pagebrowser		Called before the pagebrowser gets rendered
 * @hook	post_pagebrowser	Called after the pagebrowser has been rendered
 * @hook	pre_cObjects		Called before global cObjects get rendered
 * @hook	post_cObjects		Called after global cObjects get rendered
 * @hook	pre_filters		Called before the defined filters get retrieved
 * @hook	post_filters		Called after the defined filters have been retrieved
 * @hook	pre_assignToSmarty	Called before assigning all variables to the smarty instance
 * @hook	post_assignToSmarty	Called after all variables have been assigned to the smarty instance
 * @hook	pre_renderTemplate	Called before rendering all content/rows/cObjects/etc. into the smarty (HTML) template
 * @hook	post_renderTemplate	Called after all content/rows/cObjects/etc. has been rendered into the smarty (HTML) template
 * @hook	pre_renderUid		Called before rendering/setting the output for rendering a single UID (for XML/RSS, AJAX output, etc.)
 * @hook	post_renderUid		Called after the output for a single UID (for XML/RSS, AJAX output, etc.) has been rendered
 * @hook	late_main_ext		Called at the end of the method "main_ext"

 */
class tx_kbdisplay_pi_cached extends tslib_pibase {
	var $prefixId      = 'tx_kbdisplay_pi_cached';												// Same as class name
	var $scriptRelPath = 'pi_cached/class.tx_kbdisplay_pi_cached.php';		// Path to this script relative to the extension dir.
	var $extKey        = 'kb_display';																		// The extension key.
	var $pi_checkCHash = true;
	var $smarty_cache = false;
	var $smarty_compileDir= 'typo3temp/smarty_compile';
	var $smarty_cacheDir= 'typo3temp/smarty_cache';
	var $smarty = false;
	var $smarty_default = false;
	var $mode = 'listView';
	var $resultData = array();
	var $resultCount = 0;
	var $queryResult = false;
	var $cObjects = false;
	var $errors = array();

	// These variables will hold the class instances required for generating, query and fetching, and processing of database rows
	var $queryController = NULL;
	var $rowProcessor = NULL;

	var $page = 0;
	var $pagebrowser = 0;

	function main($content,$conf)	{
		$this->hook('early_main');
		$this->selfUid = intval($this->cObj->data['uid']);
		$this->config = $conf;
		$this->pi_setPiVarDefaults();
			// TODO: Remove this crap:
		$GLOBALS['TSFE']->includeTCA();
		$GLOBALS['TSFE']->kb_display = &$this;

		if ($this->config['startupCOA.']) {
			$this->cObj->cObjGet($this->config['startupCOA.']);
		}

		$this->hook('pre_main_ext');
		$output = $this->main_ext($content, $conf);
		$this->hook('post_main_ext');

		if ($this->config['endCOA.']) {
			$this->cObj->cObjGet($this->config['endCOA.']);
		}

		$this->hook('late_main');
		return $output;
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param		string				$content: The PlugIn content
	 * @param		array					$conf: The PlugIn configuration
	 * @return	string				The content that is displayed on the website
	 */
	function main_ext($content,$conf)	{
		// Early hook at the beginning of method
		$this->hook('early_main_ext');

		$this->renderUid = intval($this->piVars['plugin']);
		if ($this->renderUid && ($this->renderUid != $this->selfUid)) {
			return '';
		}
		$this->pi_loadLL();
		$this->pi_initPIflexForm();

		$this->startup_cObj = clone($this->cObj);

		// Check if plugin should execute any further or just return
		$disable = intval($this->cObj->stdWrap($this->config['disable'], $this->config['disable.']));
		$disableQuery = intval($this->cObj->stdWrap($this->config['disableQuery'], $this->config['disableQuery.']));
		if ($disable) {
			return '';
		}

		// Initialize the smarty object instance
		$this->hook('pre_initSmarty');
		$this->initSmarty();
		$this->hook('post_initSmarty');

		// Set up some variables requred for rendering and caching
		$this->hook('pre_setVars');
		if ($this->config['setCacheReg'] || $this->config['setCacheReg.']) {
			$this->cacheReg = intval($this->cObj->stdWrap($this->config['setCacheReg'], $this->config['setCacheReg.']));
			if ($this->cacheReg) {
				$GLOBALS['TSFE']->page_cache_reg1 = $this->cacheReg;
			}
		}
		$this->flex = &$this->cObj->data['pi_flexform'];
		$this->itemsPerPage = intval($this->pi_getFFvalue($this->flex, 'field_itemsPerPage', 'sheet_listView', 'lDEF', 'vDEF'));
		$this->browser['show'] = intval($this->pi_getFFvalue($this->flex, 'field_showPagebrowser', 'sheet_listView', 'lDEF', 'vDEF'));
		$this->browser['pages']= intval($this->pi_getFFvalue($this->flex, 'field_pagesInBrowser', 'sheet_listView', 'lDEF', 'vDEF'));
		$this->hook('post_setVars');

		// Retrieve all content from the database depending on the criteria defined in the flexform
		$this->hook('pre_getContent');
		$this->ok = $this->getContent($disableQuery);
		$this->hook('post_getContent');
		if (!$this->ok) {
			return '';
		}

		// Render the pagebrowser if required
		$this->hook('pre_pagebrowser');
		if ($this->itemsPerPage && $this->browser['show']) {
			$this->renderPagebrowser();
		}
		$this->hook('post_pagebrowser');

		// Create row processor instance and render global cObjects
		$this->hook('pre_cObjects');
		$this->initObject_rowProcessor();
		if (is_array($this->useConfig['cObjects.']) && count($this->useConfig['cObjects.'])) {
			$this->cObjects = $this->rowProcessor->get_cObjects(false, $this->useConfig['cObjects.']);
		}
		$this->hook('post_cObjects');

		// Retrieve filter parameters
		$this->hook('pre_filters');
		$this->filter['items'] = $this->get_filters();
		$this->filter['show'] = intval($this->pi_getFFvalue($this->flex, 'field_showFilters', 'sheet_filters', 'lDEF', 'vDEF'));
		$this->hook('post_filters');

		// Assign all required variable to smarty
		$this->hook('pre_assignToSmarty');
		$this->assignToSmarty();
		$this->hook('post_assignToSmarty');

		// Render the retrieved content into the template
		$this->hook('pre_renderTemplate');
		$this->content = $this->renderTemplate();
		$this->hook('post_renderTemplate');

		// If only a single uid should get rendered
		if ($this->renderUid && ($this->renderUid == $this->selfUid)) {
			$this->hook('pre_renderUid');
			$this->output = $GLOBALS['TSFE']->convOutputCharset($this->content);
			$GLOBALS['TSFE']->content = $this->output;
			$GLOBALS['TSFE']->realPageCacheContent();
			header('Content-Type: text/html; charset='.$GLOBALS['TSFE']->metaCharset);
			$this->hook('post_renderUid');
			echo $this->content;
			exit();
		}
		
		if (!$this->config['dontWrapInBaseClass']) {
			$this->content = $this->pi_wrapInBaseClass($this->content);
		}
		$this->hook('late_main_ext');
		return $this->content;
	}

	public function hook($name) {
		if (is_array($hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['kb_display']['hooks'][$name])) {
			foreach ($hooks as $hookKey => $hookConfig) {
				$params = array(
					'hook' => $name,
					'key' => $hookKey,
					'config' => $hookConfig,
				);
				if ($hookConfig['object'] && ($method = $hookConfig['method'])) {
					$object = &t3lib_div::getUserObj($hookConfig['object']);
					if (is_object($object) && method_exists($object, $method)) {
						$object->$method($params, $this);
					}
				} elseif ($hookConfig['userFunc']) {
					t3lib_div::callUserFunction($hookConfig['userFunc'], $params, $this);
				}
			}
		}
	}

	/**
	 * Returns the array of filters
	 *
	 * @return	array		The array of defined filters
	 */
	public function get_filters() {
		$filters = $this->queryController->get_filterOptions();
		return $filters;
	}

	/**
	 * Initializes the smarty object instance
	 *
	 * @return	void
	 */
	public function initSmarty() {
		$this->smarty = tx_smarty::smarty();
		$this->smarty->setSmartyVar('caching', $this->cacheSmarty);
		$this->smarty->setSmartyVar('compile_dir', $this->smarty_compileDir);
		$this->smarty->setSmartyVar('cache_dir', $this->smarty_cacheDir);
		// TODO: Disable for production use
		// $this->smarty->setSmartyVar('force_compile', true);
		$this->smarty_default = clone($this->smarty);
	}

	/**
	 * Returns a clone of the default smarty object instance
	 *
	 * @return		object			A clone of the default smarty object instance
	 */
	public function get_smartyClone() {
		return clone($this->smarty_default);
	}

	/**
	 * The rendering method. This method calls all object methods for creating and 
	 * executing the query, trasforming the result information and assigning the
	 * result to the smarty-instance.
	 *
	 * @return	boolean		If content has been rendered and should get sent to output this value is true.
	 */
	function getContent($disableQuery) {
		$this->resultData = array();
		// Load parameters submitted via GET or POST
		$this->loadParams();			// ********

		if ($this->showUid) {
			$disable = intval($this->pi_getFFvalue($this->flex, 'field_disableSingleView', 'sDEF', 'lDEF', 'vDEF'));
			if ($disable) {
				return false;
			}
		} else {
			$disable = intval($this->pi_getFFvalue($this->flex, 'field_disableListView', 'sheet_listView', 'lDEF', 'vDEF'));
			if ($disable) {
				return false;
			}
		}

		// TODO: CACHE
		// It probably would be the best idea to cache all criteria, order and query
		// information by simply serializing those 3 objects if their configuration
		// hasn't changed. Can get verified by md5 checksums.

		// INITIALIZATION:
		// Initialize the "queryController" object instance
		$this->initObject_queryController();

		// QUERY CONTROLLER:
		// Let the query controller parse the flexform
		$this->queryController->parseFlexform();

		// Transfer data of main table from query controller to its main table object
		$mainIdx = $this->queryController->tables_main_transferData();

		// Transfer data of additional tables from query controller to extra table objects
		$extraIdxArr = $this->queryController->tables_extra_transferData();

		// Let the query controller process each of its table objects: Parse criterias, on-clauses, etc.
		// This will transfer all required information to the query generator
		$this->queryController->tables_process();

		if (!$disableQuery) {
			// Let the query get executed
			$this->queryController->queryExecute();

			// Retrieve all result rows from database
			$this->queryController->fetchResult();

			// Handle all result transformations
			$this->queryController->transformResult();

			// Retrieve results
			$this->resultData = $this->queryController->getResult();

			// There are probably more results. Retrieve the number of total results.
			$this->queryController->queryExecute(true);
			$this->queryController->fetchResult(true, true);
			list($resultRow) = $this->queryController->getResult(true);
			$this->resultCount = intval($resultRow['cnt']);
		}
		return true;
	}

	/**
	 * Renders the retrieved content into the smarty template
	 *
	 * @return	string		Rendered smarty template
	 */
	public function renderTemplate() {
		$templateFile = $this->pi_getFFvalue($this->flex, 'field_templateFile_'.$this->mode, 'sDEF', 'lDEF', 'vDEF');
		$templateFile = $this->cObj->stdWrap($templateFile, $this->config['templateFile.']);
		$origTemplateFile = $templateFile;
		$templateFile = t3lib_div::getFileAbsFileName($templateFile);
		$templateDir = dirname($templateFile);

		if (!(file_exists($templateDir) && is_dir($templateDir) && file_exists($templateFile) && is_file($templateFile))) {
			return $this->pi_getLL('pi_noTemplateFile', 'No template file configured !');
		}
		$this->smarty->setSmartyVar('template_dir', $templateDir);
		return $this->smarty->display($templateFile, '', md5($templateDir));
	}

	/**
	 * Assigns all necessary variables to the smarty object instance
	 *
	 * @return	void
	 */
	public function assignToSmarty() {
		$this->smarty->assign('TYPO3_SITE_URL', t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		$this->smarty->assign('resultCount', $this->resultCount);
		$GLOBALS['T3_VARS']['kb_display']['resultCount'][$this->selfUid] = $this->resultCount;
		$this->smarty->assign('resultData', $this->resultData);
		$this->smarty->assign('cObjects', $this->cObjects);
		$this->smarty->assign('filter', $this->filter);
		$this->smarty->assign('pagebrowser', $this->pagebrowser);
		$this->smarty->assign('itemsPerPage', $this->itemsPerPage);
		$this->smarty->assign('fe_user', $GLOBALS['TSFE']->fe_user);
	}

	/**
	 * Sets all keys in the member variable '$this->pagebrowser' to appropriate values
	 *
	 * @return		void
	 */
	function renderPagebrowser() {
		$this->pagebrowser = array();
		$this->pagebrowser['show'] = true;
		$total = $this->resultCount;
		$perPage = $this->itemsPerPage;
		$current_page = $this->current_page;
		if ($perPage) {
				// Calculate total number of pages
			$total_pages = ceil($total/$perPage);
				// Check if current page is in boundaries
			if ($current_page >= $total_pages) {
				$current_page = $total_pages - 1;
			}
			if ($current_page < 0) {
				$current_page = 0;
			}
				// set start and end items/rows
			$itemStart = $perPage*$current_page;
			$itemEnd = $itemStart+$perPage;
			if ($itemEnd > $total) {
				$itemEnd = $total;
			}
			if (!$total) {
				$itemStart = -1;
				$total_pages = 1;
			}

				// Set total number of pages and current page number
			$this->pagebrowser['totalPages'] = $total_pages;
			$this->pagebrowser['currentPage'] = $current_page;


			$this->pagebrowser['itemStart'] = $itemStart+1;
			$this->pagebrowser['itemEnd'] = $itemEnd;
			$this->pagebrowser['itemsTotal'] = ($itemEnd-$itemStart);

				// If a number of pages to show is specified
			$show_pages = $this->browser['pages'];
			if ($show_pages) {
					// If current page is not the first page, set "first" and "prev" page-browser items
				if ($current_page > 0) {
					$this->pagebrowser['pages']['first'] = $this->getPageData(0, 'first');
					$this->pagebrowser['pages']['prev'] = $this->getPageData($current_page - 1, 'prev');
				}

					// Calculate start end end page of the pagebrowser
				list($start_page, $end_page) = $this->get_pageBrowser_boundaries($current_page, $show_pages, $total_pages, $this->config['listView.']['pageBrowser.']['moreBefore']);
					// Add items to the "pages" subarray of the pagebrowser for each shown page
				for ($i = $start_page; $i < $end_page; $i++) {
					$this->pagebrowser['pages'][$i] = $this->getPageData($i);
				}
					// If current page is not the last page, set "next" and "last" page-browser items
				if ($current_page < ($total_pages - 1)) {
					$this->pagebrowser['pages']['next'] = $this->getPageData($current_page + 1, 'next');
					$this->pagebrowser['pages']['last'] = $this->getPageData($total_pages - 1, 'last');
				}
			}
		}
	}


	/**
	 * Calculates the start- and end-pages shown in the page browser, depending on number of pages shown, currently selected page
	 * and total number of pages.
	 *
	 * @param			integer			The number of the currently shown page
	 * @param			integer			The number of pages to show in the browser
	 * @param			integer			The total number of pages available (dependend of number of result rows and items shown per page)
	 * @param			boolean			Defines centering of current page in browser - see comment in the top of the method
	 * @return		array				An array containing to elements, the number of the page to start with, and the number of the last page
	 */
	protected function get_pageBrowser_boundaries($current_page, $show_pages, $total_pages, $moreBefore = false) {
		// If an odd number of pages is shown in the pagebrowser, the current page can get centered. As "show_pages-1" (-1 for the current page)
		// will be an even number then. The even number can get divided by 2 without reminder. So an equal amount of pages can get shown before
		// and after the current page in the browser (i.e.:  5 6 7 <<8>> 9 10 11 -- if 8 is the current page and 7 pages get shown).
		// If an even number of pages is shown either before or after the current page one browse-page-link more is shown. (i.e.: 5 6 <<7>> 8)
		// If the line with "floor" is used, there will be more pages after the current one. If the line with "ceil" is used there will be more
		// pages before the current one.
		if ($moreBefore) {
			$show_pages_before = ceil(($show_pages-1)/2.0);
		} else {
			$show_pages_before = floor(($show_pages-1)/2.0);
		}
		$start_page = $current_page - $show_pages_before;
		if ($start_page < 0) {
			$start_page = 0;
		}
		$end_page = $start_page + $show_pages;
		if ($end_page > $total_pages) {
			$end_page = $total_pages;
			$start_page = $end_page - $show_pages;
		}
		if ($start_page < 0) {
			$start_page = 0;
		}
		return array($start_page, $end_page);
	}

	/**
	 * Returns an array entry which can get added to the "pages" key of the pagebrowser, containing information to the pages shown, and a link to them
	 *
	 * @param			integer				The number of the page
	 * @param			string				Type of page-data if this is a special page like "prev"/"next" or "first"/"last"
	 * @return		array					An array which contains information about the page and a HREF link to the page
	 */
	function getPageData($page, $type = '') {
		$linkConfig = $this->config['listView.']['pageBrowser.']['link.'];
		$this->cObj->setCurrentVal($page);
		$this->cObj->data['linkType'] = $type;
		if (!is_array($linkConfig)) {
			$linkConfig = array(
				'typolink' => 1,
				'typolink.' => array(
					'parameter.' => array(
						'data' => 'TSFE:id',
					),
					'additionalParams' => $page?('&'.$this->prefixId.'[page]='.$page):'',
					'returnLast' => 'url',
					'useCacheHash' => 1,
				),
			);
			if (is_array($this->piVars['filter']) && !$this->config['listView.']['pageBrowser.']['resetCategory']) {
				foreach ($this->piVars['filter'] as $key => $value) {
					$linkConfig['typolink.']['additionalParams'] .= '&'.$this->prefixId.'[filter]['.$key.']='.rawurlencode($value);
				}
			}
		}
		$link = $this->cObj->stdWrap($page, $linkConfig);
		return array(
			'type' => $type,
			'page' => $page,
			'link' => $link,
			'active' => ($page==$this->current_page)?true:false,
		);
	}

	/**
	 * Initialize the queryController object instance
	 *
	 * @return	void
	 */
	function initObject_queryController() {
		$this->queryController = t3lib_div::makeInstance('tx_kbdisplay_queryController');
		$this->queryController->init($this, $this);
	}



	/*************************
	 *
	 * Supporting methods
	 *
	 * Simple additional miscellaneous methods required in this class
	 *
	 *************************/

	/**
	 * Loads parameters set via GET or POST
	 *
	 * @return	void
	 */
	function loadParams() {
		$this->showUids = t3lib_div::intExplode(',', $this->piVars['view']);
		$this->showUid = intval($this->piVars['view']);
		if (is_array($this->config['showUid.'])) {
			$uidResult = $this->cObj->stdWrap($this->showUid, $this->config['showUid.']);
			$this->showUid = intval($showUid);
			$this->showUids = t3lib_div::intExplode(',', $uidResult);
		}
		$doShow = false;
		if (!$this->showUid) {
			foreach ($this->showUids as $showUid) {
				$doShow |= $showUid?true:false;
			}
		}
		if ($this->showUid || $doShow) {
			$this->mode = 'singleView';
			$this->useConfig = $this->config['singleView.'];
		} else {
			$this->useConfig = $this->config['listView.'];
		}
		$this->current_page = intval($this->piVars['page']);
	}

	/**
	 * Initialize the rowProcessor object instance
	 *
	 * @return	void
	 */
	public function initObject_rowProcessor() {
		$this->rowProcessor = t3lib_div::makeInstance('tx_kbdisplay_rowProcessor');
		$this->rowProcessor->init($this->queryController, $this);
	}

	/**
	 * Sets an error in the local errors instance variable
	 *
	 * @param		string			Type of error (ERROR, WARNING, NOTICE)
	 * @return	string			The error message
	 * @return	void
	 */
	function addError($type, $message) {
		$this->errors[] = array($type, $message);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/pi_cached/class.tx_kbdisplay_pi_cached.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kb_display/pi_cached/class.tx_kbdisplay_pi_cached.php']);
}

?>
