<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class TranslatePlugin {

	private RequestInterface $request;

	public function __construct(RequestInterface $request) {
		$this->request = $request;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): ?string {
		$key = $params['key'] ?? null;
		$default = $params['default'] ?? null;
		$htmlEscape = $params['htmlEscape'] ?? true;
		$arguments = $params['arguments'] ?? null;
		$extensionName = $params['extensionName'] ?? $this->request->getControllerExtensionName();

		$value = LocalizationUtility::translate($key, $extensionName, $arguments);
		if ($value === null) {
			$value = $default;
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

}
