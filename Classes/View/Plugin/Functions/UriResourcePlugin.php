<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

class UriResourcePlugin {

	private RequestInterface $request;

	public function __construct(RequestInterface $request) {
		$this->request = $request;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): string {
		$path = $params['path'] ?? null;
		$extensionName = $params['extensionName'] ?? null;
		$absolute = $params['absolute'] ?? false;

		if ($extensionName === null) {
			$extensionName = $this->request->getControllerExtensionName();
		}
		$uri = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
		$uri = PathUtility::getPublicResourceWebPath($uri);

		if ($absolute === true) {
			$uri = GeneralUtility::locationHeaderUrl($uri);
		}

		return $uri;
	}

}
