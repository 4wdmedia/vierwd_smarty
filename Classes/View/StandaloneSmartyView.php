<?php

namespace Vierwd\VierwdSmarty\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class StandaloneSmartyView extends SmartyView {

	/**
	 * @param ContentObjectRenderer $contentObject The current cObject. If NULL a new instance will be created
	 * @throws \InvalidArgumentException
	 * @throws \UnexpectedValueException
	 */
	public function __construct(ContentObjectRenderer $contentObject = null) {
		$this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

		$this->configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
		if ($contentObject === null) {
			$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		}
		$this->configurationManager->setContentObject($contentObject);

		$request = $this->objectManager->get(WebRequest::class);
		$request->setRequestUri(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseUri(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
		$request->setControllerExtensionName('VierwdSmarty');

		$uriBuilder = $this->objectManager->get(UriBuilder::class);
		$uriBuilder->setRequest($request);

		$controllerContext = $this->objectManager->get(ControllerContext::class);
		$controllerContext->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);

		$this->setControllerContext($controllerContext);

		$this->initializeView();
	}
}
