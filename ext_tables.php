<?php
defined('TYPO3') || die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'vierwd_smarty',
	'smarty_render',
	'Smarty Rendering'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('vierwd_smarty', 'Configuration/TypoScript', 'Smarty Content Elements');
