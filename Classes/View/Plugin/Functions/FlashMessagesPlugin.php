<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

class FlashMessagesPlugin {

	private ControllerContext $controllerContext;

	public function __construct(ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): string {
		$renderMode = $params['renderMode'] ?? 'ul';
		$class = $params['class'] ?? 'typo3-messages';
		$queueIdentifier = $params['queueIdentifier'] ?? null;

		$flashMessages = $this->controllerContext->getFlashMessageQueue($queueIdentifier)->getAllMessagesAndFlush();

		if (count($flashMessages) === 0) {
			return '';
		}

		if ($renderMode != 'div') {
			$renderMode = 'ul';
		}

		$content = '<' . $renderMode;
		if ($class) {
			$content .= ' class="' . htmlspecialchars($class) . '"';
		}
		$content .= '>';

		foreach ($flashMessages as $singleFlashMessage) {
			if ($renderMode == 'ul') {
				$content .= '<li>' . htmlspecialchars($singleFlashMessage->getMessage()) . '</li>';
			} else {
				$content .= htmlspecialchars($singleFlashMessage->getMessage());
			}
		}

		$content .= '</' . $renderMode . '>';

		return $content;
	}

}
