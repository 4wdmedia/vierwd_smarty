<?php

namespace Vierwd\VierwdSmarty\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class WrapOldElements {
	/**
	 * @var TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	public function wrap($content, $params = []) {
		$CType = $this->cObj->data['CType'];

		$doNotWrap = $params['doNotWrap'] ? GeneralUtility::trimExplode(',', $params['doNotWrap']) : [];
		if (in_array($CType, $doNotWrap)) {
			return $content;
		}

		$typoScript = $GLOBALS['TSFE']->tmpl->setup['tt_content.'][$CType];
		if ($typoScript == 'FLUIDTEMPLATE' || $typoScript == '< lib.fluidContent') {
			return $content;
		}

		$renderConfig = [
			'templateName' => 'WrapOld',
		];

		\Smarty::$_smarty_vars['capture']['content'] = $content;
		return $this->cObj->cObjGetSingle('< lib.fluidContent', $renderConfig);
	}
}
