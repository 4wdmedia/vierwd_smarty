<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Unit\View;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\ImageService;
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
	 * @test
	 */
	public function renderWithStandaloneView(): void {
		$configurationManager = $this->getMockBuilder(ConfigurationManagerInterface::class)->disableOriginalConstructor()->getMock();
		$imageService = $this->getMockBuilder(ImageService::class)->disableOriginalConstructor()->getMock();

		$view = new StandaloneSmartyView($configurationManager, $imageService);
		$view->setTemplateRootPaths(['EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates/']);

		$view->assign('variable', 'TEST');
		// @extensionScannerIgnoreLine
		$content = trim($view->render('StandaloneView.tpl'));
		$expected = "Template will be rendered with StandaloneView.\nTemplate evaluation\nTEST";
		$this->assertEquals($expected, $content);
	}

}
