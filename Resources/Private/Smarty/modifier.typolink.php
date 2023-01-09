<?php
declare(strict_types = 1);

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

function smarty_modifier_typolink(string $parameter): string {
	$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
	return $cObj->getTypoLink_URL($parameter);
}
