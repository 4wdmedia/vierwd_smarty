<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Unit\View;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\ExtensionService;

use Vierwd\VierwdSmarty\View\StandaloneSmartyView;

class StandaloneViewTest extends UnitTestCase {

	protected function setUp() {
		$resourceFactory = $this->createMock(ResourceFactory::class);
		GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

		$configurationManager = $this->getAccessibleMock(FrontendConfigurationManager::class, [], [], '', false);
		$request = $this->getAccessibleMock(WebRequest::class, [], [], '', false);
		$uriBuilder = $this->getAccessibleMock(UriBuilder::class, [], [], '', false);
		$controllerContext = $this->setupMockControllerContext('VierwdSmarty', '', 'Smarty', 'render', 'tpl');

		$extensionService = $this->getAccessibleMock(ExtensionService::class, [], [], '', false);

		$objectManager = $this->getAccessibleMock(ObjectManager::class, [], [], '', false);
		$objectManager->method('get')->will($this->returnValueMap([
			[ConfigurationManagerInterface::class, $configurationManager],
			[WebRequest::class, $request],
			[UriBuilder::class, $uriBuilder],
			[ControllerContext::class, $controllerContext],
			[ExtensionService::class, $extensionService],
		]));
		GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);
	}

	protected function tearDown() {
		GeneralUtility::purgeInstances();
	}

	/**
	 * Helper to build mock controller context needed to test expandGenericPathPattern.
	 *
	 * @param string $packageKey
	 * @param string $subPackageKey
	 * @param string $controllerName
	 * @param string $format
	 * @return ControllerContext
	 */
	protected function setupMockControllerContext($packageKey, $subPackageKey, $controllerName, $action, $format) {
		if (strpos($controllerName, '\\') === false) {
			$controllerObjectName = "TYPO3\\$packageKey\\" . ($subPackageKey != $subPackageKey . '\\' ? : '') . 'Controller\\' . $controllerName . 'Controller';
		} else {
			$controllerObjectName = $controllerName;
		}
		$mockRequest = $this->createMock(WebRequest::class);
		$mockRequest->expects($this->any())->method('getControllerExtensionKey')->will($this->returnValue('vierwd_smarty'));
		$mockRequest->expects($this->any())->method('getControllerSubPackageKey')->will($this->returnValue($subPackageKey));
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
	public function renderWithStandaloneView() {
		$view = new StandaloneSmartyView();
		$view->setTemplateRootPaths(['EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates/']);

		$view->assign('variable', 'TEST');
		// @extensionScannerIgnoreLine
		$content = trim($view->render('StandaloneView.tpl'));
		$expected = "Template will be rendered with StandaloneView.\nTemplate evaluation\nTEST";
		$this->assertEquals($expected, $content);
	}
}
