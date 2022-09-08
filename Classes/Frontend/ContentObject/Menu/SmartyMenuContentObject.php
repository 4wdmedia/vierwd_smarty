<?php

namespace Vierwd\VierwdSmarty\Frontend\ContentObject\Menu;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject;

use Vierwd\VierwdSmarty\View\SmartyView;

class SmartyMenuContentObject extends TextMenuContentObject {

	public $menuArr;

	public function writeMenu() {
		if (empty($this->menuArr)) {
			return '';
		}

		$template = $this->mconf['template'];

		if (!$template) {
			return '';
		}

		$objectManager = GeneralUtility::makeInstance(ObjectManager::class);

		$controllerContext = $objectManager->get(ControllerContext::class);

		$request = $objectManager->get(Request::class);
		$request->setControllerExtensionName($this->mconf['extensionName'] ?: 'vierwd_smarty');
		$controllerContext->setRequest($request);

		$uriBuilder = $objectManager->get(UriBuilder::class);
		$uriBuilder->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);

		$configuration = $objectManager->get(ConfigurationManagerInterface::class);
		$extensionName = GeneralUtility::underscoredToUpperCamelCase($request->getControllerExtensionName());
		$configuration = $configuration->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName);

		$view = $objectManager->get(SmartyView::class);
		$view->setControllerContext($controllerContext);
		$templateRootPaths = $configuration['view']['templateRootPaths'] ?? $configuration['settings']['templateRootPaths'] ?? [];
		// set template root paths, if available
		if ($templateRootPaths) {
			$view->setTemplateRootPaths($templateRootPaths);
		}
		$view->initializeView();

		$view->assign('level', $this->menuNumber);
		$view->assign('menu', $this->menuArr);
		$view->assign('menuObject', $this);

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
				return $this->isItemState($kind, $key);
			}
		}

		// item not found. return first item matching the uid
		foreach ($this->menuArr as $key => $menuItem) {
			if ($menuItem['uid'] === $item['uid']) {
				return $this->isItemState($kind, $key);
			}
		}

		return false;
	}

	public function subMenu($uid, $objSuffix = '') {
		$tsfe = $this->getTypoScriptFrontendController();
		$tsfe->register['parentMenu'] = $this;

		$this->I = [];
		// subMenu expects a valid I[key] to work on _SUB_MENU
		foreach ($this->menuArr as $key => $value) {
			if ($value['uid'] == $uid) {
				$this->I['key'] = $key;
			}
		}

		return parent::subMenu($uid, $objSuffix);
	}

}
