<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Functions;

use Smarty_Internal_Template;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class FlashMessagesPlugin {

	private RenderingContextInterface $renderingContext;

	public function __construct(RenderingContextInterface $renderingContext) {
		$this->renderingContext = $renderingContext;
	}

	public function __invoke(array $params, Smarty_Internal_Template $smarty): string {
		$renderMode = $params['renderMode'] ?? 'ul';
		$class = $params['class'] ?? 'typo3-messages';
		$queueIdentifier = $params['queueIdentifier'] ?? null;

		if ($queueIdentifier === null && $this->renderingContext instanceof RenderingContext) {
			$request = $this->renderingContext->getRequest();
			if (!$request instanceof RequestInterface) {
				// Throw if not an extbase request
				throw new \RuntimeException(
					'ViewHelper f:flashMessages needs an extbase Request object to resolve the Queue identifier magically.'
					. ' When not in extbase context, set attribute "queueIdentifier".',
					1639821269
				);
			}
			$extensionService = GeneralUtility::makeInstance(ExtensionService::class);
			$pluginNamespace = $extensionService->getPluginNamespace($request->getControllerExtensionName(), $request->getPluginName());
			$queueIdentifier = 'extbase.flashmessages.' . $pluginNamespace;
		}

		$flashMessageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier($queueIdentifier);
		$flashMessages = $flashMessageQueue->getAllMessagesAndFlush();

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
