<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\ViewHelper;

use Psr\Http\Message\ServerRequestInterface;
use Smarty;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
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

	protected static ?SmartyView $smartyView = null;

	public function initialize(): void {
		if (self::$smartyView === null) {
			self::$smartyView = GeneralUtility::makeInstance(SmartyView::class);
		}
	}

	public function render(): string {
		if (self::$smartyView === null) {
			return '';
		}
		$template = $this->renderChildren();

		$request = $this->createExtbaseRequest();

		$view = self::$smartyView;
		$view->setRequest($request);
		$view->initializeView();

		assert($view->Smarty instanceof Smarty);

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

		// @extensionScannerIgnoreLine
		$result = $view->render('string:' . $template);

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

	private function createExtbaseRequest(): Request {
		$request = $this->renderingContext->getAttribute(ServerRequestInterface::class);

		$extbaseAttribute = new ExtbaseRequestParameters();
		$extbaseAttribute->setPluginName('pi1');
		$extbaseAttribute->setControllerExtensionName('VierwdSmarty');
		$extbaseAttribute->setControllerName($this->renderingContext->getControllerName());
		$extbaseAttribute->setControllerActionName($this->renderingContext->getControllerAction());
		$request = $request->withAttribute('extbase', $extbaseAttribute);
		$request = GeneralUtility::makeInstance(Request::class, $request);
		return $request;
	}

}
