<?php

########################################################################
# Extension Manager/Repository config file for ext: "kb_display"
#
# Auto generated 24-03-2010 16:24
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'KB Display',
	'description' => 'This extension allows you to display list and detail view of ANY table for which a TCA array is configured. No more "list/detail" FE-plugin coding. Makes life a lot easier. Documentation on forge wiki: http://forge.typo3.org/wiki/extension-kb_display',
	'category' => 'fe',
	'shy' => 0,
	'dependencies' => 'cms,smarty',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/kb_display/',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Kraft Bernhard',
	'author_email' => 'kraftb@think-open.at',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.1.0-0.0.0',
			'php' => '5.1.0-0.0.0',
			'cms' => '',
			'smarty' => '1.4.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:82:{s:9:"Changelog";s:4:"6519";s:8:"DOKU.txt";s:4:"5c79";s:8:"TODO.txt";s:4:"390c";s:32:"class.tx_kbdisplay_itemsProc.php";s:4:"6ce2";s:12:"ext_icon.gif";s:4:"488d";s:17:"ext_localconf.php";s:4:"8654";s:14:"ext_tables.php";s:4:"830d";s:13:"locallang.xml";s:4:"dca2";s:16:"locallang_db.xml";s:4:"7291";s:36:"compareTypes/boolean/compareType.tpl";s:4:"7519";s:37:"compareTypes/datetime/compareType.tpl";s:4:"3b1a";s:36:"compareTypes/integer/compareType.tpl";s:4:"a2cf";s:33:"compareTypes/list/compareType.tpl";s:4:"df52";s:37:"compareTypes/list_csv/compareType.tpl";s:4:"2b29";s:35:"compareTypes/string/compareType.tpl";s:4:"3158";s:38:"compareTypes/timestamp/compareType.tpl";s:4:"3b1a";s:40:"hooks/class.tx_kbdisplay_content_ext.php";s:4:"bcb8";s:36:"hooks/class.tx_kbdisplay_getData.php";s:4:"8d03";s:36:"hooks/class.tx_kbdisplay_realurl.php";s:4:"df76";s:35:"hooks/class.tx_kbdisplay_smarty.php";s:4:"7a10";s:36:"hooks/class.tx_kbdisplay_stdWrap.php";s:4:"06a5";s:40:"hooks/class.tx_kbdisplay_t3libbefunc.php";s:4:"32ce";s:37:"hooks/class.tx_kbdisplay_t3libdiv.php";s:4:"4d84";s:41:"hooks/class.tx_kbdisplay_t3libtcemain.php";s:4:"cce7";s:46:"hooks/smarty_plugins/modifier.mysql_escape.php";s:4:"b951";s:50:"hooks/smarty_plugins/modifier.mysql_escapelike.php";s:4:"597a";s:37:"lib/class.tx_kbdisplay_flexFields.php";s:4:"3d6a";s:42:"lib/class.tx_kbdisplay_queryController.php";s:4:"d640";s:40:"lib/class.tx_kbdisplay_queryCriteria.php";s:4:"2687";s:39:"lib/class.tx_kbdisplay_queryFetcher.php";s:4:"9d6c";s:41:"lib/class.tx_kbdisplay_queryGenerator.php";s:4:"b194";s:37:"lib/class.tx_kbdisplay_queryOrder.php";s:4:"7c28";s:37:"lib/class.tx_kbdisplay_queryTable.php";s:4:"d946";s:39:"lib/class.tx_kbdisplay_rowProcessor.php";s:4:"b6f7";s:20:"pi_cached/ce_wiz.gif";s:4:"02b6";s:42:"pi_cached/class.tx_kbdisplay_pi_cached.php";s:4:"8c43";s:50:"pi_cached/class.tx_kbdisplay_pi_cached_wizicon.php";s:4:"7cf0";s:23:"pi_cached/locallang.xml";s:4:"0159";s:29:"res/flexform_ds_pi_cached.xml";s:4:"d00c";s:39:"res/flexform_ds_pi_cached__criteria.xml";s:4:"3b81";s:55:"res/flexform_ds_pi_cached__criteriaCompareField_tpl.xml";s:4:"3ccf";s:52:"res/flexform_ds_pi_cached__criteriaConnector_tpl.xml";s:4:"fcd8";s:43:"res/flexform_ds_pi_cached__criteria_tpl.xml";s:4:"2aee";s:38:"res/flexform_ds_pi_cached__filters.xml";s:4:"2b3b";s:39:"res/flexform_ds_pi_cached__listView.xml";s:4:"60c0";s:35:"res/flexform_ds_pi_cached__sDEF.xml";s:4:"6794";s:37:"res/flexform_ds_pi_cached__search.xml";s:4:"ab7d";s:38:"res/flexform_ds_pi_cached__sorting.xml";s:4:"cd56";s:42:"res/flexform_ds_pi_cached__sorting_tpl.xml";s:4:"d6e5";s:46:"res/flexform_ds_pi_cached__subcriteria_tpl.xml";s:4:"adc7";s:37:"res/flexform_ds_pi_cached__tables.xml";s:4:"918d";s:53:"res/compare_fields/flexform__compare_compareField.xml";s:4:"6fe8";s:47:"res/compare_fields/flexform__compare_custom.xml";s:4:"8578";s:52:"res/compare_fields/flexform__compare_defaultNone.xml";s:4:"c232";s:47:"res/compare_fields/flexform__compare_negate.xml";s:4:"27e3";s:51:"res/compare_fields/flexform__compare_type__date.xml";s:4:"e674";s:56:"res/compare_fields/flexform__compare_type__multibool.xml";s:4:"6652";s:53:"res/compare_fields/flexform__compare_type__number.xml";s:4:"bef2";s:53:"res/compare_fields/flexform__compare_type__string.xml";s:4:"5c53";s:51:"res/compare_fields/flexform__compare_type__time.xml";s:4:"4128";s:48:"res/compare_fields/flexform__compare_usersel.xml";s:4:"d852";s:52:"res/compare_fields/flexform__compare_value__bool.xml";s:4:"c75a";s:57:"res/compare_fields/flexform__compare_value__cruser_id.xml";s:4:"c4b4";s:52:"res/compare_fields/flexform__compare_value__date.xml";s:4:"20dc";s:58:"res/compare_fields/flexform__compare_value__dateoffset.xml";s:4:"c323";s:56:"res/compare_fields/flexform__compare_value__datetime.xml";s:4:"a203";s:54:"res/compare_fields/flexform__compare_value__double.xml";s:4:"96c3";s:56:"res/compare_fields/flexform__compare_value__fe_group.xml";s:4:"fcab";s:51:"res/compare_fields/flexform__compare_value__int.xml";s:4:"4268";s:51:"res/compare_fields/flexform__compare_value__pid.xml";s:4:"c3b5";s:54:"res/compare_fields/flexform__compare_value__string.xml";s:4:"1145";s:52:"res/compare_fields/flexform__compare_value__time.xml";s:4:"b4d0";s:55:"res/compare_fields/flexform__compare_value__timesec.xml";s:4:"7065";s:51:"res/compare_fields/flexform__compare_value__uid.xml";s:4:"0617";s:52:"res/compare_fields/flexform__compare_value__year.xml";s:4:"648e";s:40:"res/sorting/flexform__sorting_custom.xml";s:4:"8db9";s:43:"res/sorting/flexform__sorting_direction.xml";s:4:"3546";s:39:"res/sorting/flexform__sorting_field.xml";s:4:"b904";s:60:"res/todo/flexform_ds_pi_cached__criteriaCompareField_tpl.xml";s:4:"6031";s:44:"res/todo/flexform_ds_pi_cached__ordering.xml";s:4:"64f2";s:42:"res/todo/flexform_ds_pi_cached__search.xml";s:4:"808f";s:34:"xclass/class.ux_t3lib_tceforms.php";s:4:"28f9";}',
	'suggests' => array(
	),
);

?>