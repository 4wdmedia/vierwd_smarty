<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ImageService;

class StandaloneSmartyView extends SmartyView {

	public function __construct(ConfigurationManagerInterface $configurationManager, ImageService $imageService) {
		parent::__construct($configurationManager, $imageService);

		$extbaseAttribute = new ExtbaseRequestParameters();
		$extbaseAttribute->setPluginName('pi1');
		$extbaseAttribute->setControllerExtensionName('VierwdSmarty');
		$extbaseAttribute->setControllerName('Smarty');
		$extbaseAttribute->setControllerActionName('render');

		$request = $GLOBALS['TYPO3_REQUEST'];
		$request = $request->withAttribute('extbase', $extbaseAttribute);
		$request = GeneralUtility::makeInstance(Request::class, $request);

		$this->setRequest($request);

		$this->initializeView();
	}

}
