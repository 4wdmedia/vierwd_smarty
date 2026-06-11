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
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use Vierwd\VierwdSmarty\Tests\Functional\ExtensionTestCase;
use Vierwd\VierwdSmarty\View\StandaloneSmartyView;

class StandaloneViewTest extends ExtensionTestCase {

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
	public function renderWithStandaloneView(): void {
		$view = $this->createView();

		$view->assign('variable', 'TEST');
		$content = trim($view->render('StandaloneView.tpl'));
		$expected = "Template will be rendered with StandaloneView.\nTemplate evaluation\nTEST";

		$this->assertEquals($expected, $content);
	}

	private function createView(): StandaloneSmartyView {
		$configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->disableOriginalConstructor()->getMock();
		$configurationManager->method('getConfiguration')->willReturn([]);
		$imageService = $this->getMockBuilder(ImageService::class)->disableOriginalConstructor()->getMock();
		$typoLinkCodecService = $this->getMockBuilder(TypoLinkCodecService::class)->disableOriginalConstructor()->getMock();

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

		$view = new StandaloneSmartyView($configurationManager, $imageService, $typoLinkCodecService);
		$view->setRequest($request);
		$view->getRenderingContext()->getTemplatePaths()->setTemplateRootPaths([
			GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates'),
		]);
		$view->initializeView();

		return $view;
	}

}
