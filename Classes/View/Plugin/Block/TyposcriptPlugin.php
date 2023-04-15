<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Block;

use Smarty_Internal_Template;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TyposcriptPlugin {

	private ?ContentObjectRenderer $contentObject = null;

	public function __construct(ContentObjectRenderer $contentObject) {
		$this->contentObject = $contentObject;
	}

	public function __invoke(array $params, ?string $content, Smarty_Internal_Template $smarty, bool &$repeat): string {
		if (!isset($content)) {
			return '';
		}

		$data = isset($params['data']) ? $params['data'] : [];
		unset($params['data']);
		$data = $params + $data;

		$table = isset($data['table']) ? $data['table'] : '_NO_TABLE';
		unset($data['table']);

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		if ($this->contentObject) {
			$cObj->setParent($this->contentObject->data, $this->contentObject->currentRecord);
			$cObj->currentRecordNumber = $this->contentObject->currentRecordNumber;
			$cObj->parentRecordNumber = $this->contentObject->parentRecordNumber;
		}
		if ($table != '_NO_TABLE') {
			$data['_MIGRATED'] = false;
		}
		$cObj->start($data, $table);

		// $cObj->setCurrentVal($dataValues[$key][$valueKey]);

		$tokenizer = GeneralUtility::makeInstance(LossyTokenizer::class);
		$lineStream = $tokenizer->tokenize($content);
		$astBuilder = GeneralUtility::makeInstance(AstBuilder::class);
		$root = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupTree();
		$typoScriptConfig = $astBuilder->build($lineStream, $root)->toArray();

		$content = $cObj->cObjGet($typoScriptConfig, 'COA');

		if (!empty($params['assign'])) {
			$smarty->assign($params['assign'], $content);
			return '';
		} else {
			return $content;
		}
	}

}
