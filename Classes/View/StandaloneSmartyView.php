<?php

namespace Vierwd\VierwdSmarty\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

class StandaloneSmartyView extends SmartyView {

	public function __construct(ConfigurationManagerInterface $configurationManager, ImageService $imageService, TypoLinkCodecService $typoLinkCodecService) {
		parent::__construct($configurationManager, $imageService, $typoLinkCodecService);

		$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$configurationManager->setContentObject($contentObject);

		$request = GeneralUtility::makeInstance(WebRequest::class);
		$request->setControllerExtensionName('VierwdSmarty');

		$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
		$uriBuilder->setRequest($request);

		$controllerContext = GeneralUtility::makeInstance(ControllerContext::class);
		$controllerContext->setRequest($request);
		$controllerContext->setUriBuilder($uriBuilder);

		$this->setControllerContext($controllerContext);

		$this->initializeView();
	}

}
