<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Unit\ViewHelper;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\TemplateView;

use Vierwd\VierwdSmarty\Tests\Functional\ExtensionTestCase;
use Vierwd\VierwdSmarty\View\SmartyView;
use Vierwd\VierwdSmarty\ViewHelper\SmartyViewHelper;

class SmartyViewHelperTest extends ExtensionTestCase {

	use ProphecyTrait;

	private ?RenderingContextInterface $context = null;

	protected bool $initializeDatabase = false;

	protected function setUp(): void {
		parent::setUp();

		$resourceFactory = $this->createMock(ResourceFactory::class);
		GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

		$extensionService = $this->createMock(ExtensionService::class);
		GeneralUtility::setSingletonInstance(ExtensionService::class, $extensionService);

		$uriBuilder = $this->getMockBuilder(UriBuilder::class)->disableOriginalConstructor()->getMock();
		GeneralUtility::addInstance(UriBuilder::class, $uriBuilder);

		$configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->disableOriginalConstructor()->getMock();
		$imageService = $this->getMockBuilder(ImageService::class)->disableOriginalConstructor()->getMock();

		$this->context = $this->get(RenderingContextFactory::class)->create();
		$request = new ServerRequest();
		$this->context->setAttribute(ServerRequestInterface::class, $request);
		$smartyView = new SmartyView($configurationManager, $imageService, $this->context);

		GeneralUtility::addInstance(SmartyView::class, $smartyView);
	}

	protected function tearDown(): void {
		GeneralUtility::purgeInstances();
		parent::tearDown();

		$this->context = null;

		$reflection = new \ReflectionProperty(SmartyViewHelper::class, 'smartyView');
		$reflection->setAccessible(true);
		$reflection->setValue(null);
	}

	/**
	 * @test
	 */
	public function smartyInFluidCode(): void {
		$this->context->getTemplatePaths()->setTemplateSource('{namespace vierwd=Vierwd\VierwdSmarty\ViewHelper}<vierwd:smarty>{$variable = \'test\'}{$variable|upper}{$variable}</vierwd:smarty>');
		self::assertSame('TESTtest', (new TemplateView($this->context))->render());
	}

}
