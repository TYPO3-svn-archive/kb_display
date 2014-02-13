<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
define('PATH_kb_display', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY));
define('RELPATH_kb_display', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY));

// Hooks which enable new content objects "EXT_CONTENT" and "SMARTY"
// @todo: Check EXT_CONTENT what it does and if there is no easier solution (i.e. extending ContentObjectRenderer and overwriting methods)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array('EXT_CONTENT', 'thinkopen_at\kbDisplay\Hooks\ContentObject\ExtContent');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array('SMARTY', 'thinkopen_at\kbDisplay\Hooks\ContentObject\Smarty');

// Hooks which extend the ContentObjectRenderer by some features (additional stdWrap properties and getData fields)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getData']['kb_display_getData'] = 'thinkopen_at\kbDisplay\Hooks\ContentObject\GetData';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['kb_display_stdWrap'] = 'thinkopen_at\kbDisplay\Hooks\ContentObject\StdWrap';

// Other miscellaneous hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['kb_display'] = 'thinkopen_at\kbDisplay\Hooks\DataHandlerClearCache->clearCaches';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass']['kb_display'] = 'thinkopen_at\kbDisplay\Hooks\DynamicFlexforms';

// What is this hook good for?
// $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['kb_display_cHash'] = 'thinkopen_at\kbDisplay\Hooks\RealUrl->override_cHash';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'pi_cached/class.tx_kbdisplay_pi_cached.php', '_pi_cached', 'list_type', 1);


$_EXTCONF = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugQuery'] = intval($_EXTCONF['debugQuery']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugFilterQuery'] = intval($_EXTCONF['debugFilterQuery']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugDynamicFlexforms'] = intval($_EXTCONF['debugDynamicFlexforms']);

