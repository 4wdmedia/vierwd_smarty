<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Functional\Resource;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Smarty;
use SmartyException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Vierwd\VierwdSmarty\Resource\ExtResource;
use Vierwd\VierwdSmarty\Tests\Functional\ExtensionTestCase;

class ExtResourceTest extends ExtensionTestCase {

	/** @phpstan-var Smarty */
	protected ?Smarty $smarty;

	protected vfsStreamDirectory $root;

	protected bool $initializeDatabase = false;

	protected function setUp(): void {
		parent::setUp();

		$this->smarty = new Smarty();
		$this->smarty->registerResource('EXT', new ExtResource());

		$this->root = vfsStream::setup('root');

		$this->smarty->setCompileDir(vfsStream::url('root/templates_c'));
		$this->smarty->setCacheDir(vfsStream::url('root/cache'));
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->smarty = null;
		GeneralUtility::purgeInstances();
	}

	/**
	 * @test
	 */
	public function fetchReturnsTemplateContent(): void {
		$actual = trim($this->smarty->fetch('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates/ExtResourceTemplate.tpl'));
		$expected = "Template will be rendered with EXT: resource.\nTemplate evaluation";
		$this->assertEquals($expected, $actual, 'Fetching templates with EXT: url does not work.');
	}

	/**
	 * @test
	 */
	public function includeUsingExtReturnsTemplateContent(): void {
		$actual = trim($this->smarty->fetch('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates/ExtResourceTemplateWithInclude.tpl'));
		$expected = "Before\nTemplate will be rendered with EXT: resource.\nTemplate evaluation\nAfter";
		$this->assertEquals($expected, $actual, 'Fetching templates with EXT: url does not work.');
	}

	/**
	 * @test
	 */
	public function fetchNonExistentTemplate(): void {
		$this->expectException(SmartyException::class);
		$this->smarty->fetch('EXT:vierwd_smarty/Tests/Unit/Fixtures/Templates/ExtResourceTemplateDoesNotExist.tpl');
	}

}
