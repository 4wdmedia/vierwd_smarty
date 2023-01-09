<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View\Plugin\Block;

use Smarty_Internal_Template;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
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

		$tsparserObj = GeneralUtility::makeInstance(TypoScriptParser::class);

		if (is_array($GLOBALS['TSFE']->tmpl->setup)) {
			foreach ($GLOBALS['TSFE']->tmpl->setup as $tsObjectKey => $tsObjectValue) {
				// do not copy int-keys
				if ($tsObjectKey !== intval($tsObjectKey) && $tsObjectKey !== intval($tsObjectKey) . '.') {
					$tsparserObj->setup[$tsObjectKey] = $tsObjectValue;
				}
			}
		}

		$conditionMatcher = GeneralUtility::makeInstance(ConditionMatcher::class);
		$tsparserObj->parse($content, $conditionMatcher);

		// save current typoscript setup and change to modified setup
		$oldSetup = $GLOBALS['TSFE']->tmpl->setup;
		$GLOBALS['TSFE']->tmpl->setup = $tsparserObj->setup;

		$oldTplVars = $smarty->tpl_vars;
		$smarty->tpl_vars = [];

		$content = $cObj->cObjGet($tsparserObj->setup, 'COA');

		$smarty->tpl_vars = $oldTplVars;

		// reset typoscript
		$GLOBALS['TSFE']->tmpl->setup = $oldSetup;

		if (!empty($params['assign'])) {
			$smarty->assign($params['assign'], $content);
			return '';
		} else {
			return $content;
		}
	}

}
