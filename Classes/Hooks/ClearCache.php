<?php

namespace Vierwd\VierwdSmarty\Hooks;

/***************************************************************
*  Copyright notice
*
*  (c) 2012 Robert Vock <robert.vock@4wdmedia.de>
*  All rights reserved
*
***************************************************************/

class ClearCache implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface {

	/**
	 * Adds the option to clear the Smarty Template cache in the back-end clear cache menu.
	 *
	 * @param array $a_cacheActions
	 * @param array $a_optionValues
	 * @return void
	 * @see typo3/interfaces/backend_cacheActionsHook#manipulateCacheActions($cacheActions, $optionValues)
	 */
	public function manipulateCacheActions(&$a_cacheActions, &$a_optionValues) {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			// $s_title = $GLOBALS['LANG']->sL('LLL:EXT:realurl_clearcache/locallang.xml:rm.clearCacheMenu_realUrlClearCache', true);
			$s_title = 'Smarty Cache leeren';
			$s_imagePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('vierwd_smarty');
			$a_cacheActions[] = array(
				'id'    => 'realurl_cache',
				'title' => $s_title,
				'href' => 'ajax.php?ajaxID=tx_vierwdsmarty::clear',
				'icon'  => '<img src="'.$s_imagePath.'ext_icon.gif" title="'.$s_title.'" alt="'.$s_title.'" />',
			);
			$a_optionValues[] = 'clearCacheVierwdSmarty';
		}
	}
	
	/**
	 * Clears the actual Smarty Template Cache
	 */
	public static function clear() {
		$GLOBALS['typo3CacheManager']->getCache('vierwd_smarty')->flush();
	}
}

?>