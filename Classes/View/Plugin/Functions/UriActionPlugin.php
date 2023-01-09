<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

class UriActionPlugin {

	private ControllerContext $controllerContext;

	public function __construct(ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): ?string {
		$action = $params['action'] ?? null;
		$arguments = $params['arguments'] ?? [];
		$controller = $params['controller'] ?? null;
		$extensionName = $params['extensionName'] ?? null;
		$pluginName = $params['pluginName'] ?? null;
		$pageUid = $params['pageUid'] ?? 0;
		$pageType = $params['pageType'] ?? 0;
		$noCache = $params['noCache'] ?? false;
		$section = $params['section'] ?? '';
		$format = $params['format'] ?? '';
		$linkAccessRestrictedPages = $params['linkAccessRestrictedPages'] ?? false;
		$additionalParams = $params['additionalParams'] ?? [];
		$absolute = $params['absolute'] ?? false;
		$addQueryString = $params['addQueryString'] ?? false;
		$argumentsToBeExcludedFromQueryString = $params['argumentsToBeExcludedFromQueryString'] ?? [];

		$uriBuilder = $this->controllerContext->getUriBuilder()->reset();
		if ($pageUid) {
			$uriBuilder->setTargetPageUid($pageUid);
		}
		$uri = $uriBuilder
			->setTargetPageType($pageType)
			->setNoCache($noCache)
			->setSection($section)
			->setFormat($format)
			->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
			->setArguments($additionalParams)
			->setCreateAbsoluteUri($absolute)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
			->uriFor($action, $arguments, $controller, $extensionName, $pluginName);

		return $uri;
	}

}
