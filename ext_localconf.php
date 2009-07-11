<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
} 
define('PATH_kb_display', t3lib_extMgm::extPath($_EXTKEY));
define('RELPATH_kb_display', t3lib_extMgm::extRelPath($_EXTKEY));

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass']['kb_display_includes'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_t3libbefunc.php:&tx_kbdisplay_t3libbefunc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['kb_display_stdWrap'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_stdWrap.php:&tx_kbdisplay_stdWrap';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getData']['kb_display_getData'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_getData.php:&tx_kbdisplay_getData';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array('SMARTY', 'EXT:kb_display/hooks/class.tx_kbdisplay_smarty.php:tx_kbdisplay_smarty');



$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['cHashParamsHook']['kb_display_cHash'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_t3libdiv.php:&tx_kbdisplay_t3libdiv->cHashParams';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['kb_display_cHash'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_realurl.php:&tx_kbdisplay_realurl->override_cHash';

$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['t3lib/class.t3lib_tceforms.php'] = t3lib_extMgm::extPath($_EXTKEY).'xclass/class.ux_t3lib_tceforms.php';

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi_cached/class.tx_kbdisplay_pi_cached.php', '_pi_cached', 'list_type', 1);

?>
