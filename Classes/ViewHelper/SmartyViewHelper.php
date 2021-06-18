<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\ViewHelper;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext as FluidRenderingContext;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use Vierwd\VierwdSmarty\View\SmartyView;

/**
 * Use Smarty template logic within Fluid Templates.
 *
 * Example
 * =======
 *
 *    <f:format.raw>
 *        <vierwd:smarty>
 *            {$variable = 'test'}
 *            {$variable|upper}
 *            {include 'Partials/SmartyFile.tpl'}
 *        </vierwd:smarty>
 *    </f:format.raw>
 * </code>
 */
class SmartyViewHelper extends AbstractViewHelper {

	/** @var \Vierwd\VierwdSmarty\View\SmartyView */
	static protected $smartyView = null;

	public function initialize() {
		if (self::$smartyView === null) {
			self::$smartyView = GeneralUtility::makeInstance(SmartyView::class);
		}
	}

	public function render(): string {
		if (self::$smartyView === null) {
			return '';
		}
		$template = $this->renderChildren();

		$parentView = $this->renderingContext->getViewHelperVariableContainer()->getView();

		$view = self::$smartyView;
		if ($this->renderingContext instanceof FluidRenderingContext) {
			$view->setControllerContext($this->renderingContext->getControllerContext());
		}
		if ($parentView instanceof AbstractTemplateView) {
			$view->setTemplateRootPaths($parentView->getTemplateRootPaths());
		}
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
		$variables = (array)$templateVariableContainer->getAll();
		foreach ($variables as $key => &$value) {
			// @phpstan-ignore-next-line
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
