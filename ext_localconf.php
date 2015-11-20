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

// **************
// AutomaticStandaloneView
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Fluid\\View\\StandaloneView'] = array(
	'className' => 'Vierwd\\VierwdSmarty\\View\\AutomaticStandaloneView',
);

?>