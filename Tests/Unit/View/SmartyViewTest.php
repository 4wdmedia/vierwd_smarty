<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Unit\View;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdSmarty\View\SmartyView;
use function Vierwd\VierwdSmarty\View\clean;

class SmartyViewTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
		$resourceFactory = $this->createMock(ResourceFactory::class);
		GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

		$extensionService = $this->createMock(ExtensionService::class);
		GeneralUtility::setSingletonInstance(ExtensionService::class, $extensionService);
	}

	protected function tearDown(): void {
		unset($GLOBALS['TSFE']);
		GeneralUtility::purgeInstances();
		parent::tearDown();
	}

	/**
	 * Helper to build mock controller context needed to test expandGenericPathPattern.
	 *
	 * @param string $packageKey
	 * @param string $controllerName
	 * @param string $format
	 * @return ControllerContext
	 */
	protected function setupMockControllerContext($packageKey, $controllerName, $action, $format) {
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
	public function getTemplateRootPathsReturnsUserSpecifiedTemplatePaths() {
		/** @var SmartyView|\PHPUnit_Framework_MockObject_MockObject|\Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface $templateView */
		$templateView = $this->getAccessibleMock(SmartyView::class, ['dummy'], [], '', false);
		$templateView->setTemplateRootPaths(['/foo/bar']);
		$expected = ['/foo/bar'];
		$actual = $templateView->_call('getTemplateRootPaths');
		$this->assertEquals($expected, $actual, 'A set template root path was not returned correctly.');
	}

	/**
	 * @test
	 */
	public function calculateTemplatePath() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', 'My', 'action', 'tpl');

		$templateView = $this->getAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->_set('controllerContext', $mockControllerContext);

		$expected = 'vierwd_smarty/Resources/Private/Templates';
		$actual = $templateView->_call('getTemplateRootPaths');
		$this->assertIsArray($actual);
		$this->assertStringEndsWith($expected, $actual[0]);
	}

	/**
	 * @test
	 */
	public function renderTemplate() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', 'Controller', 'action', 'tpl');
		$templateView = $this->getAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$templateView->initializeView();

		$templateView->assign('templateVariable', 'test-string');
		$this->assertEquals('test-string', $templateView->render());

		// assert escaping works
		$templateView->assign('templateVariable', '<b>test</b>');
		$this->assertNotEquals('<b>test</b>', $templateView->render());
	}

	/**
	 * Smarty 3.1.30 had a bug with array_shift in loops.
	 * @see https://github.com/smarty-php/smarty/issues/291
	 * @test
	 */
	public function checkSimpleTemplateLogic() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', 'Controller', 'action', 'tpl');
		$templateView = $this->getAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$templateView->initializeView();

		// @extensionScannerIgnoreLine
		$this->assertEquals('44321', $templateView->render('TemplateLogic.tpl'));
	}

	/**
	 * @test
	 */
	public function checkTemplateWhitespaceIsStripped() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', 'Controller', 'action', 'tpl');
		$templateView = $this->getAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$templateView->initializeView();

		// @extensionScannerIgnoreLine
		$this->assertEquals('LineBreaks and trailing spacesmultiple breaksWhitespace after commentWhitespace after multiline comment', $templateView->render('Whitespace.tpl'));
	}

	/**
	 * @test
	 */
	public function testEmailLink() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', 'Controller', 'action', 'tpl');
		$templateView = $this->getAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$mockContentObject = $this->getAccessibleMock(ContentObjectRenderer::class, null, [], '', false);
		$templateView->setContentObject($mockContentObject);

		$templateView->initializeView();
		// @extensionScannerIgnoreLine
		$this->assertEquals('<a href="mailto:example@example.com" class="mail">example@example.com</a>', $templateView->render('string:{email address="example@example.com"}'));
	}

	/**
	 * @test
	 */
	public function cleanValues() {
		$this->assertEquals('&lt;test&gt;', clean('<test>'));
		$this->assertEquals('100', clean(100));
		$this->assertEquals('', clean(null));

		$this->expectException(\Exception::class); // phpcs:ignore
		clean(['Array cannot be cleaned']);
	}
}
