<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

class UriResourcePlugin {

	private ControllerContext $controllerContext;

	public function __construct(ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): string {
		$path = $params['path'] ?? null;
		$extensionName = $params['extensionName'] ?? null;
		$absolute = $params['absolute'] ?? false;

		if ($extensionName === null) {
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}
		$uri = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
		$uri = GeneralUtility::getFileAbsFileName($uri);
		$uri = PathUtility::stripPathSitePrefix($uri);

		if ($absolute === false && $uri !== false && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
			$uri = '../' . $uri;
		}

		if ($absolute === true) {
			$uri = $this->controllerContext->getRequest()->getBaseURI() . $uri;
		}

		return $uri;
	}

}
