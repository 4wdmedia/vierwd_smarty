<?php

namespace Vierwd\VierwdSmarty\Cache;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Robert Vock <robert.vock@4wdmedia.de>
*  All rights reserved
*
***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClearCacheHook implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface {

	/**
	 * Adds the option to clear the Smarty Template cache in the backend clear cache menu.
	 *
	 * @param array $cacheActions
	 * @param array $optionValues
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			// $title = $GLOBALS['LANG']->sL('LLL:EXT:realurl_clearcache/locallang.xml:rm.clearCacheMenu_realUrlClearCache', true);
			$title = 'Smarty Cache leeren';
			$imagePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('vierwd_smarty');
			$cacheActions[] = array(
				'id' => 'vierwd_smarty',
				'title' => 'LLL:EXT:vierwd_smarty/Resources/Private/Language/locallang.xml:flushTemplateCache',
				'description' => 'LLL:EXT:vierwd_smarty/Resources/Private/Language/locallang.xml:flushTemplateCache.description',
				'href' => BackendUtility::getModuleUrl('tce_db', ['cacheCmd' => 'vierwd_smarty']),
				'iconIdentifier' => 'actions-system-cache-clear-impact-medium',
			);
			$optionValues[] = 'vierwd_smarty';
		}
	}

	public function clear(array $params) {
		if ($params['cacheCmd'] === 'vierwd_smarty') {
			$cacheManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
			$cacheManager->flushCachesInGroup('vierwd_smarty');
		}
	}
}
