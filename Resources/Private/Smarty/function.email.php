<?php
declare(strict_types = 1);

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

function smarty_function_email(array $params, Smarty_Internal_Template $smarty): string {
	$address = $params['address'] ?? false;
	$label = $params['label'] ?? $address;
	$parameter = $params['parameter'] ?? $address . ' - mail';
	$ATagParams = $params['ATagParams'] ?? false;

	$conf = [
		'parameter' => $parameter,
		'ATagParams' => $ATagParams,
	];

	$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

	return $cObj->typoLink($label, $conf);
}
