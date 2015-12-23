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

		$view = $objectManager->get(\Vierwd\VierwdSmarty\View\SmartyView::class);
		$view->setControllerContext($controllerContext);
		$view->initializeView();

		$view->assign('level', $this->menuNumber);
		$view->assign('menu', $this->menuArr);
		$view->assign('menuObject', $this);

		return $view->render($template);
	}
}

