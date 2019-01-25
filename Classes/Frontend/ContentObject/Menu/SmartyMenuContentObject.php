<?php

namespace Vierwd\VierwdSmarty\Frontend\ContentObject\Menu;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject;

use Vierwd\VierwdSmarty\View\SmartyView;

class SmartyMenuContentObject extends TextMenuContentObject {

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

		$request = $objectManager->get(\TYPO3\CMS\Extbase\Mvc\Request::class);
		$request->setControllerExtensionName($this->mconf['extensionName'] ? $this->mconf['extensionName'] : 'vierwd_smarty');
		$controllerContext->setRequest($request);

		$uriBuilder = $objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
		$uriBuilder->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);

		$configuration = $objectManager->get(ConfigurationManagerInterface::class);
		$settings = $configuration->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, $request->getControllerExtensionName());

		$view = $objectManager->get(SmartyView::class);
		$view->setControllerContext($controllerContext);
		// set template root paths, if available
		if (isset($settings['templateRootPaths'])) {
			$view->setTemplateRootPaths($settings['templateRootPaths']);
		}
		$view->initializeView();

		$view->assign('level', $this->menuNumber);
		$view->assign('menu', $this->menuArr);
		$view->assign('menuObject', $this);

		return $view->render($template);
	}

	public function subMenu($uid, $objSuffix = '', $key = false) {
		$tsfe = $this->getTypoScriptFrontendController();
		$tsfe->register['parentMenu'] = $this;

		$this->I = [];
		if ($key !== false) {
			$this->I['key'] = $key;
		} else {
			// subMenu expects a valid I[key] to work on _SUB_MENU
			foreach ($this->menuArr as $key => $value) {
				if ($value['uid'] == $uid) {
					$this->I['key'] = $key;
				}
			}
		}

		return parent::subMenu($uid, $objSuffix);
	}
}
