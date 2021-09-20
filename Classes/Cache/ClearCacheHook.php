<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Cache;

use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClearCacheHook {

	/**
	 * Adds the option to clear the Smarty Template cache in the backend clear cache menu.
	 */
	public function __invoke(ModifyClearCacheActionsEvent $event): void {
		// &$cacheActions, &$optionValues
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
			$uri = $uriBuilder->buildUriFromRoute('tce_db', ['cacheCmd' => 'vierwd_smarty']);
			$event->addCacheAction([
				'id' => 'vierwd_smarty',
				'title' => 'LLL:EXT:vierwd_smarty/Resources/Private/Language/locallang.xlf:flushTemplateCache',
				'description' => 'LLL:EXT:vierwd_smarty/Resources/Private/Language/locallang.xlf:flushTemplateCache.description',
				'href' => $uri,
				'iconIdentifier' => 'actions-system-cache-clear-impact-medium',
			]);
			$event->addCacheActionIdentifier('vierwd_smarty');
		}
	}

	public function clear(array $params): void {
		$cacheCmd = $params['cacheCmd'] ?? null;
		if (in_array($cacheCmd, ['vierwd_smarty', 'all'])) {
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
