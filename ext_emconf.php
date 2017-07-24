<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "vierwd_smarty".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'Extbase Smarty',
	'description' => 'Enables the Smarty template engine for extbase views. Smarty is easier to use than fluid.',
	'category' => 'misc',
	'author' => 'Robert Vock',
	'author_email' => 'robert.vock@4wdmedia.de',
	'author_company' => 'FORWARD MEDIA',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '2.7.4',
	'constraints' => [
		'depends' => [
			'php' => '5.5',
			'typo3' => '6.2.0-8.9.99',
		],
		'conflicts' => [
		],
		'suggests' => [
			'fluid_styled_content' => '*',
		],
	],
];
