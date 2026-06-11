<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Functional\View;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use Vierwd\VierwdSmarty\Tests\Functional\ExtensionTestCase;
use Vierwd\VierwdSmarty\View\SmartyView;

class SmartyViewRenderingTest extends ExtensionTestCase {

	private ?RenderingContextInterface $renderingContext = null;

	protected bool $initializeDatabase = false;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'][] = GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Resources/Private/Smarty');

		$resourceFactory = $this->createMock(ResourceFactory::class);
		GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

		$extensionService = $this->createMock(ExtensionService::class);
		$extensionService->method('getPluginNamespace')->willReturn('tx_vierwdsmarty_pi1');
		GeneralUtility::setSingletonInstance(ExtensionService::class, $extensionService);

		$uriBuilder = $this->createMock(UriBuilder::class);
		GeneralUtility::addInstance(UriBuilder::class, $uriBuilder);

		$viewFactory = $this->createMock(ViewFactoryInterface::class);
		GeneralUtility::addInstance(ViewFactoryInterface::class, $viewFactory);

		$this->renderingContext = $this->get(RenderingContextFactory::class)->create();
	}

	protected function tearDown(): void {
		unset($GLOBALS['TYPO3_REQUEST']);
		GeneralUtility::purgeInstances();
		$this->renderingContext = null;

		parent::tearDown();
	}

	#[Test]
	public function renderTemplateEscapesHtml(): void {
		$view = $this->createView();
		$view->assign('templateVariable', '<b>test</b>');

		$result = $view->render('Controller/Action.tpl');

		$this->assertSame('&lt;b&gt;test&lt;/b&gt;', $result);
	}

	#[Test]
	public function renderTemplateReturnsAssignedValue(): void {
		$view = $this->createView();
		$view->assign('templateVariable', 'test-string');

		$result = $view->render('Controller/Action.tpl');

		$this->assertSame('test-string', $result);
	}

	/**
	 * Smarty 3.1.30 had a bug with array_shift in loops.
	 * @see https://github.com/smarty-php/smarty/issues/291
	 */
	#[Test]
	public function checkSimpleTemplateLogic(): void {
		$view = $this->createView();

		$this->assertSame('44321', $view->render('TemplateLogic.tpl'));
	}

	#[Test]
	public function checkTemplateWhitespaceIsStripped(): void {
		$view = $this->createView();

		$this->assertSame('LineBreaks and trailing spacesmultiple breaksWhitespace after commentWhitespace after multiline comment', $view->render('Whitespace.tpl'));
	}

	#[Test]
	public function testEmailLink(): void {
		$view = $this->createView(true);

		$this->assertSame('<a href="mailto:example@example.com" class="mail">example@example.com</a>', $view->render('string:{email address="example@example.com"}'));
	}

	#[Test]
	public function testTyposcript(): void {
		$view = $this->createView(true);

		$this->assertSame('Test', $view->render('string:' . implode("\n", [
			'{typoscript}',
			'10 = TEXT',
			'10.value = Test',
			'{/typoscript}',
		])));
	}

	private function createView(bool $withContentObject = false): SmartyView {
		$configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->disableOriginalConstructor()->getMock();
		$configurationManager->method('getConfiguration')->willReturn([]);
		$imageService = $this->getMockBuilder(ImageService::class)->disableOriginalConstructor()->getMock();

		$extbaseAttribute = new ExtbaseRequestParameters();
		$extbaseAttribute->setPluginName('Pi1');
		$extbaseAttribute->setControllerExtensionName('VierwdSmarty');
		$extbaseAttribute->setControllerName('Smarty');
		$extbaseAttribute->setControllerActionName('render');

		$serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseAttribute);
		$serverRequest = $serverRequest->withAttribute('frontend.typoscript', new class {

			public function getConfigArray(): array {
				return [];
			}

			public function getSetupTree(): RootNode {
				return new RootNode();
			}

		});
		$GLOBALS['TYPO3_REQUEST'] = $serverRequest;

		$request = GeneralUtility::makeInstance(Request::class, $serverRequest);

		$view = new SmartyView($configurationManager, $imageService, $this->renderingContext);
		$view->setRequest($request);
		if ($withContentObject) {
			$contentObject = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock();
			$contentObject->method('typoLink')->willReturn('<a href="mailto:example@example.com" class="mail">example@example.com</a>');
			$contentObject->method('cObjGet')->willReturn('Test');
			GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObject);
			$view->setContentObject($contentObject);
		}
		$view->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);
		$view->initializeView();

		return $view;
	}

}
