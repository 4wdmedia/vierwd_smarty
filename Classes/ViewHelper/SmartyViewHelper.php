<?php

namespace Vierwd\VierwdSmarty\ViewHelper;

use Vierwd\VierwdSmarty\View\SmartyView;

class SmartyViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {
	/**
	 * @var Vierwd\VierwdSmarty\View\SmartyView
	 */
	static protected $smartyView;

	public function initialize() {
		if (!self::$smartyView) {
			self::$smartyView = $this->objectManager->get(SmartyView::class);
		}
	}

	public function render() {
		$template = $this->renderChildren();

		$parentView = $this->renderingContext->getViewHelperVariableContainer()->getView();

		$view = self::$smartyView;
		$view->setControllerContext($this->controllerContext);
		$view->setTemplateRootPaths($parentView->getTemplateRootPaths());
		$view->setParentView($parentView);
		$view->initializeView();
		$view->assignMultiple($this->renderingContext->getTemplateVariableContainer()->getAll());

		return $view->render('string:' . $template);
	}
}