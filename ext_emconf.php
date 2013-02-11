<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "kb_display".
 *
 * Auto generated 04-02-2013 11:50
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

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
	'suggests' => array(
	),
);

?>
