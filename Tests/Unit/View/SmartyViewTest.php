<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Tests\Unit\View;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdSmarty\View\SmartyView;
use function Vierwd\VierwdSmarty\View\clean;

final class SmartyViewTest extends UnitTestCase {

	#[Test]
	public function cleanValues(): void {
		class_exists(SmartyView::class);
		$this->assertEquals('&lt;test&gt;', clean('<test>'));
		$this->assertEquals('100', clean(100));
		$this->assertEquals('', clean(null));

		$this->expectException(\Exception::class); // phpcs:ignore
		clean(['Array cannot be cleaned']);
	}

}
