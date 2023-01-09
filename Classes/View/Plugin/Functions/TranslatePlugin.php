<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class TranslatePlugin {

	private ControllerContext $controllerContext;

	public function __construct(ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): ?string {
		$request = $this->controllerContext->getRequest();

		$key = $params['key'] ?? null;
		$default = $params['default'] ?? null;
		$htmlEscape = $params['htmlEscape'] ?? true;
		$arguments = $params['arguments'] ?? null;
		$extensionName = $params['extensionName'] ?? $request->getControllerExtensionName();

		$value = LocalizationUtility::translate($key, $extensionName, $arguments);
		if ($value === null) {
			$value = $default;
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

}
