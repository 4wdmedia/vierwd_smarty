<?php
defined('TYPO3_MODE') || die('Access denied.');

// Define TypoScript as content rendering template
$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'vierwdsmarty/Configuration/TypoScript/';
$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'vierwdsmarty/Configuration/TypoScript/v8/';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	$_EXTKEY,
	'smarty_render',
	// Controller actions
	['Smarty' => 'render',],
	// non-cacheable actions
	[]
);

// SMARTY Menu Object
$menuContentObjectFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory::class);
$menuContentObjectFactory->registerMenuType('SMARTY', \Vierwd\VierwdSmarty\Frontend\ContentObject\Menu\SmartyMenuContentObject::class);
unset($menuContentObjectFactory);
