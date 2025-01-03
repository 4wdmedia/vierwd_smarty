<?php
defined('TYPO3') || die();

// Define TypoScript as content rendering template
$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'vierwdsmarty/Configuration/TypoScript/';

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'VierwdSmarty',
	'smarty_render',
	[\Vierwd\VierwdSmarty\Controller\SmartyController::class => 'render',],
	[],
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
);

// SMARTY Menu Object
$menuContentObjectFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory::class);
$menuContentObjectFactory->registerMenuType('SMARTY', \Vierwd\VierwdSmarty\Frontend\ContentObject\Menu\SmartyMenuContentObject::class);
unset($menuContentObjectFactory);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['vierwd_smarty_cache'] = [
	'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
	'backend' => \Vierwd\VierwdSmarty\Cache\CacheBackend::class,
	'options' => ['cacheType' => 'cache'],
	'groups' => ['all', 'vierwd_smarty'],
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['vierwd_smarty_compile'] = [
	'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
	'backend' => \Vierwd\VierwdSmarty\Cache\CacheBackend::class,
	'options' => ['cacheType' => 'templates_c'],
	'groups' => ['all', 'vierwd_smarty'],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'Vierwd\\VierwdSmarty\\Cache\\ClearCacheHook->clear';

// Setup Plugins directoy
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'][] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('vierwd_smarty', 'Resources/Private/Smarty');
