<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Unit\View;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdSmarty\View\StandaloneSmartyView;

class StandaloneViewTest extends UnitTestCase {

	protected function setUp(): void {
		$GLOBALS['TYPO3_REQUEST'] = new ServerRequest();

		$resourceFactory = $this->createMock(ResourceFactory::class);
		GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

		$extensionService = $this->createMock(ExtensionService::class);
		GeneralUtility::setSingletonInstance(ExtensionService::class, $extensionService);
	}

	protected function tearDown(): void {
		GeneralUtility::purgeInstances();
		unset($GLOBALS['TYPO3_REQUEST']);
		parent::tearDown();
	}

	/**
	 * Helper to build mock controller context needed to test expandGenericPathPattern.
	 *
	 * @param string $packageKey
	 * @param string $controllerName
	 * @param string $format
	 */
	protected function setupMockControllerContext($packageKey, $controllerName, $action, $format): ControllerContext {
		if (strpos($controllerName, '\\') === false) {
			$controllerObjectName = "TYPO3\\$packageKey\\Controller\\" . $controllerName . 'Controller';
		} else {
			$controllerObjectName = $controllerName;
		}
		$mockRequest = $this->createMock(Request::class);
		$mockRequest->expects($this->any())->method('getControllerExtensionKey')->will($this->returnValue('vierwd_smarty'));
		$mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
		$mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($action));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));

		$mockControllerContext = $this->createMock(ControllerContext::class);
		$mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		return $mockControllerContext;
	}

	/**
	 * @test
	 */
	public function renderWithStandaloneView(): void {
		$configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->disableOriginalConstructor()->getMock();
		$imageService = $this->getMockBuilder(ImageService::class)->disableOriginalConstructor()->getMock();
		$typoLinkCodecService = $this->getMockBuilder(TypoLinkCodecService::class)->disableOriginalConstructor()->getMock();

		$view = new StandaloneSmartyView($configurationManager, $imageService, $typoLinkCodecService);
		$view->setTemplateRootPaths(['EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates/']);

		$view->assign('variable', 'TEST');
		// @extensionScannerIgnoreLine
		$content = trim($view->render('StandaloneView.tpl'));
		$expected = "Template will be rendered with StandaloneView.\nTemplate evaluation\nTEST";
		$this->assertEquals($expected, $content);
	}

}
