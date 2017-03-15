<?php

namespace Vierwd\VierwdSmarty\Tests\Unit\View;

use Vierwd\VierwdSmarty\View\SmartyView;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

class SmartyViewTest extends UnitTestCase {

	// function getAccessibleMock($originalClassName, $methods = [], array $arguments = [], $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true);

	/**
	 * Like get Accessible Mock, but with correct signature (no type-hint for $methods)
	 */
	protected function getOwnAccessibleMock(
		$originalClassName, $methods = [], array $arguments = [], $mockClassName = '',
		$callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true
	) {
		if ($originalClassName === '') {
			throw new \InvalidArgumentException('$originalClassName must not be empty.', 1334701880);
		}

		return $this->getMock(
			$this->buildAccessibleProxy($originalClassName),
			$methods,
			$arguments,
			$mockClassName,
			$callOriginalConstructor,
			$callOriginalClone,
			$callAutoload
		);
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
		$controllerObjectName = "TYPO3\\$packageKey\\" . ($subPackageKey != $subPackageKey . '\\' ? : '') . 'Controller\\' . $controllerName . 'Controller';
		$mockRequest = $this->getMock(WebRequest::class);
		$mockRequest->expects($this->any())->method('getControllerExtensionKey')->will($this->returnValue('vierwd_smarty'));
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($packageKey));
		$mockRequest->expects($this->any())->method('getControllerSubPackageKey')->will($this->returnValue($subPackageKey));
		$mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
		$mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($action));
		$mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
		$mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));

		$mockControllerContext = $this->getMock(ControllerContext::class, ['getRequest'], [], '', false);
		$mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		return $mockControllerContext;
	}

	/**
	 * @test
	 */
	public function getTemplateRootPathsReturnsUserSpecifiedTemplatePaths() {
		/** @var SmartyView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
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
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', null, 'My', 'action', 'tpl');

		$templateView = $this->getOwnAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->_set('controllerContext', $mockControllerContext);

		$expected = 'vierwd_smarty/Resources/Private/Templates';
		$actual = $templateView->_call('getTemplateRootPaths');
		$this->assertInternalType('array', $actual);
		$this->assertStringEndsWith($expected, $actual[0]);
	}

	/**
	 * @test
	 */
	public function canRenderFindsTemplate() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', null, 'Controller', 'action', 'html');
		$templateView = $this->getOwnAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$this->assertTrue($templateView->canRender($mockControllerContext));

		$mockControllerContext = $this->setupMockControllerContext('MyPackage', null, 'Controller', 'action', 'tpl');
		$templateView = $this->getOwnAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$this->assertTrue($templateView->canRender($mockControllerContext));
	}

	/**
	 * @test
	 */
	public function renderTemplate() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', null, 'Controller', 'action', 'tpl');
		$templateView = $this->getOwnAccessibleMock(SmartyView::class, null, [], '', false);
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
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', null, 'Controller', 'action', 'tpl');
		$templateView = $this->getOwnAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$templateView->initializeView();

		$this->assertEquals('44321', $templateView->render('TemplateLogic.tpl'));
	}

	/**
	 * @test
	 */
	public function checkTemplateWhitespaceIsStripped() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', null, 'Controller', 'action', 'tpl');
		$templateView = $this->getOwnAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$templateView->initializeView();

		$this->assertEquals('LineBreaks and trailing spacesmultiple breaks', $templateView->render('Whitespace.tpl'));
	}

	/**
	 * @test
	 */
	public function testEmailLink() {
		$mockControllerContext = $this->setupMockControllerContext('MyPackage', null, 'Controller', 'action', 'tpl');
		$templateView = $this->getOwnAccessibleMock(SmartyView::class, null, [], '', false);
		$templateView->setControllerContext($mockControllerContext);
		$templateView->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);

		$mockContentObject = $this->getOwnAccessibleMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, null, [], '', false);
		$templateView->setContentObject($mockContentObject);

		$templateView->initializeView();
		$this->assertEquals('<a href="mailto:example@example.com" class="mail">example@example.com</a>', $templateView->render('string:{email address="example@example.com"}'));
	}
}
