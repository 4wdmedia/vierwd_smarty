<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

class TypolinkPlugin {

	private ControllerContext $controllerContext;

	public function __construct(ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): string {
		$pageUid = $params['pageUid'] ?? null;
		$additionalParams = $params['additionalParams'] ?? [];
		$pageType = $params['pageType'] ?? 0;
		$noCache = $params['noCache'] ?? false;
		$linkAccessRestrictedPages = $params['linkAccessRestrictedPages'] ?? false;
		$absolute = $params['absolute'] ?? false;
		$section = $params['section'] ?? '';
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
			->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
			->setArguments($additionalParams)
			->setCreateAbsoluteUri($absolute)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
			->build();

		return $uri;
	}

}
