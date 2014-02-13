<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi_cached']='layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi_cached']='pi_flexform';

t3lib_extMgm::addPlugin(array('LLL:EXT:kb_display/locallang_db.xml:tt_content.list_type_pi_cached', $_EXTKEY.'_pi_cached'), 'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi_cached', 'FILE:EXT:kb_display/res/flexform_ds_pi_cached.xml');

if (TYPO3_MODE=="BE") {
	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_kbdisplay_pi_cached_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi_cached/class.tx_kbdisplay_pi_cached_wizicon.php';
}

