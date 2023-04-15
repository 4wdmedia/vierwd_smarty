<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Block;

use Smarty_Internal_Template;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class LinkActionPlugin {

	private UriBuilder $uriBuilder;

	public function __construct(UriBuilder $uriBuilder) {
		$this->uriBuilder = $uriBuilder;
	}

	public function __invoke(array $params, ?string $content, Smarty_Internal_Template $smarty, bool &$repeat): string {
		if (!isset($content)) {
			return '';
		}

		$defaultUrlParams = [
			'action' => null,
			'arguments' => [],
			'controller' => null,
			'extensionName' => null,
			'pluginName' => null,
			'pageUid' => null,
			'pageType' => 0,
			'noCache' => false,
			'section' => '',
			'format' => '',
			'linkAccessRestrictedPages' => false,
			'additionalParams' => [],
			'absolute' => false,
			'addQueryString' => false,
			'argumentsToBeExcludedFromQueryString' => [],
		];

		$attributes = array_diff_key($params, $defaultUrlParams);

		if (!isset($attributes['href'])) {
			$action = $params['action'] ?? $defaultUrlParams['action'];
			$arguments = $params['arguments'] ?? $defaultUrlParams['arguments'];
			$controller = $params['controller'] ?? $defaultUrlParams['controller'];
			$extensionName = $params['extensionName'] ?? $defaultUrlParams['extensionName'];
			$pluginName = $params['pluginName'] ?? $defaultUrlParams['pluginName'];
			$pageUid = $params['pageUid'] ?? $defaultUrlParams['pageUid'];
			$pageType = $params['pageType'] ?? $defaultUrlParams['pageType'];
			$noCache = $params['noCache'] ?? $defaultUrlParams['noCache'];
			$section = $params['section'] ?? $defaultUrlParams['section'];
			$format = $params['format'] ?? $defaultUrlParams['format'];
			$linkAccessRestrictedPages = $params['linkAccessRestrictedPages'] ?? $defaultUrlParams['linkAccessRestrictedPages'];
			$additionalParams = $params['additionalParams'] ?? $defaultUrlParams['additionalParams'];
			$absolute = $params['absolute'] ?? $defaultUrlParams['absolute'];
			$addQueryString = $params['addQueryString'] ?? $defaultUrlParams['addQueryString'];
			$argumentsToBeExcludedFromQueryString = $params['argumentsToBeExcludedFromQueryString'] ?? $defaultUrlParams['argumentsToBeExcludedFromQueryString'];

			$uriBuilder = $this->uriBuilder->reset();
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
			$attributes['href'] = $uri;
		}

		return '<a ' . GeneralUtility::implodeAttributes($attributes, false, true) . '>' . $content . '</a>';
	}

}
