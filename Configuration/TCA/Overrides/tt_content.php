<?php
defined('TYPO3') || exit;

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'vierwd_smarty',
	'smarty_render',
	'Smarty Rendering'
);
$CTypeKey = array_key_last($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items']);
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][$CTypeKey]['adminOnly'] = true;
unset($CTypeKey);
