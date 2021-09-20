<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Unit\ViewHelper;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;

use Vierwd\VierwdSmarty\View\SmartyView;
use Vierwd\VierwdSmarty\ViewHelper\SmartyViewHelper;

class SmartyViewHelperTest extends ViewHelperBaseTestcase {

	use ProphecyTrait;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $viewHelper;

	protected function setUp(): void {
		parent::setUp();

		$this->request->getControllerExtensionName()->willReturn('VierwdSmarty');
		$this->request->getControllerExtensionKey()->willReturn('vierwd_smarty');
		$this->request->getControllerName()->willReturn('Smarty');
		$this->request->getControllerActionName()->willReturn('render');
		$this->request->getPluginName()->willReturn('Pi1');

		$this->viewHelper = $this->getMockBuilder(SmartyViewHelper::class)->setMethods(['renderChildren'])->getMock();
		$this->viewHelper->method('renderChildren')->will($this->returnValue('{$variable = \'test\'}{$variable|upper}{$variable}'));

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$resourceFactory = $this->createMock(ResourceFactory::class);
		GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

		$imageService = $this->getMockBuilder(ImageService::class)->disableOriginalConstructor()->getMock();
		$typoLinkCodecService = $this->getMockBuilder(TypoLinkCodecService::class)->disableOriginalConstructor()->getMock();
		$smartyView = new SmartyView($imageService, $typoLinkCodecService);
		GeneralUtility::addInstance(SmartyView::class, $smartyView);
	}

	protected function tearDown(): void {
		GeneralUtility::purgeInstances();
		parent::tearDown();

		$reflection = new \ReflectionProperty(SmartyViewHelper::class, 'smartyView');
		$reflection->setAccessible(true);
		$reflection->setValue(null);
	}

	/**
	 * @test
	 */
	public function smartyInFluidCode() {
		$this->assertEquals('TESTtest', $this->viewHelper->initializeArgumentsAndRender());
	}

	/**
	 * @test
	 */
	public function smartyInFluidCodeWithoutInitialization() {
		// calling render without initalizing the ViewHelper.
		// no SmartyView = no output
		$this->assertEquals('', $this->viewHelper->render());
	}

	/**
	 * @test
	 */
	public function updateTemplateVariables() {
		$this->templateVariableContainer = $this->getMockBuilder(StandardVariableProvider::class)->getMock();
		$this->templateVariableContainer->method('getAll')->will($this->returnValue(['variable' => 'valueBefore']));
		$this->renderingContext->setVariableProvider($this->templateVariableContainer);

		$this->viewHelper = $this->getMockBuilder(SmartyViewHelper::class)->setMethods(['renderChildren'])->getMock();
		$this->viewHelper->method('renderChildren')->will($this->returnValue('{$variable}'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->assertEquals('valueBefore', $this->viewHelper->initializeArgumentsAndRender());
	}
}
