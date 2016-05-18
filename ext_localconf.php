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

// SMARTY Menu Object
$menuContentObjectFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory::class);
$menuContentObjectFactory->registerMenuType('SMARTY', \Vierwd\VierwdSmarty\Frontend\ContentObject\Menu\SmartyMenuContentObject::class);
unset($menuContentObjectFactory);

?>