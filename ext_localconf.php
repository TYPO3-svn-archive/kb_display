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
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array('EXT_CONTENT', 'EXT:kb_display/hooks/class.tx_kbdisplay_content_ext.php:tx_kbdisplay_content_ext');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['kb_display'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_t3libtcemain.php:&tx_kbdisplay_t3libtcemain->clearCaches';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['cHashParamsHook']['kb_display_cHash'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_t3libdiv.php:&tx_kbdisplay_t3libdiv->cHashParams';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['kb_display_cHash'] = 'EXT:kb_display/hooks/class.tx_kbdisplay_realurl.php:&tx_kbdisplay_realurl->override_cHash';

$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['t3lib/class.t3lib_tceforms.php'] = t3lib_extMgm::extPath($_EXTKEY).'xclass/class.ux_t3lib_tceforms.php';

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi_cached/class.tx_kbdisplay_pi_cached.php', '_pi_cached', 'list_type', 1);


$_EXTCONF = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugQuery'] = intval($_EXTCONF['debugQuery']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugFilterQuery'] = intval($_EXTCONF['debugFilterQuery']);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['debugDynamicFlexforms'] = intval($_EXTCONF['debugDynamicFlexforms']);

	function storeTiming($timing, $filename = 'time', $debug = array()) {
		$current = array_shift($timing);
		$result = array();
		foreach ($timing as $func => $time) {
			$result[$func] = $time - $current;
			$current = $time;
		}
		$fd = fopen(PATH_site.'fileadmin/'.$filename.'.log', 'ab');
		fwrite($fd, "\nLogging timing:\n".print_r($result, true)."\n".print_r($debug, true)."\n\n");
		fclose($fd);
	}

?>

