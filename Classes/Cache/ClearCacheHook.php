<?php

namespace Vierwd\VierwdSmarty\Cache;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Robert Vock <robert.vock@4wdmedia.de>
*  All rights reserved
*
***************************************************************/

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClearCacheHook implements ClearCacheActionsHookInterface {

	/**
	 * Adds the option to clear the Smarty Template cache in the backend clear cache menu.
	 *
	 * @param array $cacheActions
	 * @param array $optionValues
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
			$uri = $uriBuilder->buildUriFromRoute('tce_db', ['cacheCmd' => 'vierwd_smarty']);
			$cacheActions[] = [
				'id' => 'vierwd_smarty',
				'title' => 'LLL:EXT:vierwd_smarty/Resources/Private/Language/locallang.xlf:flushTemplateCache',
				'description' => 'LLL:EXT:vierwd_smarty/Resources/Private/Language/locallang.xlf:flushTemplateCache.description',
				'href' => $uri,
				'iconIdentifier' => 'actions-system-cache-clear-impact-medium',
			];
			$optionValues[] = 'vierwd_smarty';
		}
	}

	public function clear(array $params) {
		if ($params['cacheCmd'] === 'vierwd_smarty' || $params['cacheCmd'] === 'all') {
			try {
				$cacheManager = GeneralUtility::makeInstance(CacheManager::class);
				$cacheManager->flushCachesInGroup('vierwd_smarty');
			} catch (NoSuchCacheGroupException $e) {
				// ignore
				// TODO: Check if this hook is still needed
			}
		}
	}
}
