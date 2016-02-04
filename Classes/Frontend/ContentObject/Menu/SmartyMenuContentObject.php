<?php

namespace Vierwd\VierwdSmarty\Frontend\ContentObject\Menu;

class SmartyMenuContentObject extends \TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject {

	public function writeMenu() {
		if (empty($this->menuArr)) {
			return '';
		}

		$template = $this->mconf['template'];

		if (!$template) {
			return '';
		}

		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

		$controllerContext = $objectManager->get(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class);

		$request = $objectManager->get(\TYPO3\CMS\Extbase\Mvc\Request::class);
		$request->setControllerExtensionName($this->mconf['extensionName'] ? $this->mconf['extensionName'] : 'vierwd_smarty');
		$controllerContext->setRequest($request);

		$uriBuilder = $objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
		$uriBuilder->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);

		$view = $objectManager->get(\Vierwd\VierwdSmarty\View\SmartyView::class);
		$view->setControllerContext($controllerContext);
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

