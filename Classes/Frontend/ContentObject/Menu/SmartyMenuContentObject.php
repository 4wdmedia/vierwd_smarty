<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Frontend\ContentObject\Menu;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject;

use Vierwd\VierwdSmarty\View\SmartyView;

class SmartyMenuContentObject extends TextMenuContentObject {

	/** @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint */
	public $menuArr;

	public function writeMenu(): string {
		if (empty($this->menuArr)) {
			return '';
		}

		$template = $this->mconf['template'];

		if (!$template) {
			return '';
		}

		if ($this->mconf['copyTouchOnlyNavpoint'] ?? false) {
			// Copy the first item of the parent menu for touch-only devices
			$pid = $this->menuArr[0]['pid'];
			foreach ($this->parentMenuArr as $item) {
				if ($item['uid'] === $pid) {
					if ($item['doktype'] === PageRepository::DOKTYPE_SHORTCUT && $item['shortcut_mode'] === PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE) {
						break;
					}
					if ($item['subtitle'] ?? false) {
						// Copy subtitle as alternative "touchOnly" title
						$item['title'] = $item['subtitle'];
					}
					$item['touchOnly'] = true;
					array_unshift($this->menuArr, $item);
				}
			}
		}

		assert($this->request instanceof ServerRequestInterface);

		$extbaseAttribute = new ExtbaseRequestParameters();
		$extbaseAttribute->setPluginName('pi1');
		$extbaseAttribute->setControllerExtensionName($this->mconf['extensionName'] ?: 'VierwdSmarty');
		$extbaseAttribute->setControllerName('Smarty');
		$extbaseAttribute->setControllerActionName('render');
		$request = $this->request->withAttribute('extbase', $extbaseAttribute);
		$request = GeneralUtility::makeInstance(Request::class, $request);

		$configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
		$extensionName = GeneralUtility::underscoredToUpperCamelCase($request->getControllerExtensionName());
		$configuration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK, $extensionName);

		$view = GeneralUtility::makeInstance(SmartyView::class);
		$view->setRequest($request);
		$templateRootPaths = $configuration['view']['templateRootPaths'] ?? $configuration['settings']['templateRootPaths'] ?? [];
		// set template root paths, if available
		if ($templateRootPaths) {
			$view->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths($templateRootPaths);
		}
		$view->initializeView();

		if (!empty($this->mconf['dataProcessing.'])) {
			$contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);

			$variables = [];
			$variables = $contentDataProcessor->process($this->getParentContentObject(), $this->mconf, $variables);
			$view->assignMultiple($variables);
		}

		$view->assign('level', $this->menuNumber);
		$view->assign('menu', $this->menuArr);
		$view->assign('menuObject', $this);

		// @extensionScannerIgnoreLine
		return $view->render($template);
	}

	/**
	 * check the state of a menu-item.
	 * This method is used as a wrapper around isItemState which does not use the $key for menuArr, but the $item itself.
	 * It's also public because it's used within the templates and the item-state methods are protected since TYPO3 v9.
	 *
	 * @param string $kind ACT, IFSUB, CUR etc
	 */
	public function checkItemState(string $kind, array $item): bool {
		if ($item['ITEM_STATE'] ?? false) {
			if ((string)$item['ITEM_STATE'] === (string)$kind) {
				return true;
			}

			if ($kind === 'ACT' && $item['ITEM_STATE'] === 'ACTIFSUB') {
				return true;
			}

			if ($kind === 'IFSUB' && $item['ITEM_STATE'] === 'ACTIFSUB') {
				return true;
			}
		}

		foreach ($this->menuArr as $key => $menuItem) {
			if ($menuItem === $item) {
				// @extensionScannerIgnoreLine
				return $this->isItemState($kind, $key);
			}
		}

		// item not found. return first item matching the uid
		foreach ($this->menuArr as $key => $menuItem) {
			if ($menuItem['uid'] === $item['uid']) {
				// @extensionScannerIgnoreLine
				return $this->isItemState($kind, $key);
			}
		}

		return false;
	}

	public function subMenu(int $uid, string $objSuffix = '', int $menuItemKey = 0): string {
		$tsfe = $this->getTypoScriptFrontendController();
		$tsfe->register['parentMenu'] = $this;

		$this->I = [];
		// subMenu expects a valid I[key] to work on _SUB_MENU
		foreach ($this->menuArr as $key => $value) {
			if ($value['uid'] == $uid) {
				$this->I['key'] = $key;
			}
		}

		return parent::subMenu($uid, $objSuffix, $menuItemKey);
	}

}
