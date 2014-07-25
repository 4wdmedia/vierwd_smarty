<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	$_EXTKEY,
	'smarty_render',
	array(
		'Smarty' => 'render',
	),
	// non-cacheable actions
	array(
	)
);

if (!function_exists('utf8_deaccent')) {
	require_once PATH_site . 'typo3conf/ext/' . $_EXTKEY . '/utf8.php';
}
?>