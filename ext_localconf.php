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

// ClearCache action
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = \Vierwd\VierwdSmarty\Cache\ClearCacheHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['vierwd_smarty_cache'] = [
	'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
	'backend' => \Vierwd\VierwdSmarty\Cache\CacheBackend::class,
	'options' => ['cacheType' => 'cache'],
	'groups' => ['all', 'pages', 'vierwd_smarty'],
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['vierwd_smarty_compile'] = [
	'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
	'backend' => \Vierwd\VierwdSmarty\Cache\CacheBackend::class,
	'options' => ['cacheType' => 'templates_c'],
	'groups' => ['all', 'pages', 'vierwd_smarty'],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'Vierwd\\VierwdSmarty\\Cache\\ClearCacheHook->clear';

// Setup Plugins directoy
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'][] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY, 'Resources/Private/Smarty');

// Update location of TypoScript Template
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\Vierwd\VierwdSmarty\Install\Updates\MigrateFscStaticTemplateUpdate::class] = \Vierwd\VierwdSmarty\Install\Updates\MigrateFscStaticTemplateUpdate::class;
