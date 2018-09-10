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

		if (!$view->hasTopLevelViewHelper) {
			$isTopLevel = true;
			$view->hasTopLevelViewHelper = true;
			$smartyVariables = [];
		} else {
			$isTopLevel = false;
			$smartyVariables = $view->Smarty->getTemplateVars();
		}

		$templateVariableContainer = $this->renderingContext->getVariableProvider();
		$variables = $templateVariableContainer->getAll();
		foreach ($variables as $key => &$value) {
			if (!$view->hasTopLevelViewHelper || !isset($smartyVariables[$key])) {
				$view->Smarty->assignByRef($key, $value);
			}
			unset($value);
		}

		$result = $view->render($template);

		// if the variables have changed in a template, update the original variables in the outer templateVariableContainer
		// It's not possible to really assign them by reference
		$variablesAfterRendering = $view->Smarty->getTemplateVars();
		foreach ($variables as $key => $value) {
			if (isset($variablesAfterRendering[$key])) {
				$templateVariableContainer->remove($key);
				$templateVariableContainer->add($key, $value);
			}
		}

		if ($isTopLevel) {
			$view->hasTopLevelViewHelper = false;
		}

		return $result;
	}
}
