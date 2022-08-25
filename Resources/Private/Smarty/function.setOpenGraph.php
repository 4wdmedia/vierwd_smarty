<?php
declare(strict_types = 1);

/**
 * set a global value which can be used in TypoScript with `data = global:OpenGraph|description`
 */
function smarty_function_setOpenGraph(array $params): void {
	if (empty($GLOBALS['OpenGraph'])) {
		$GLOBALS['OpenGraph'] = [];
	}

	$GLOBALS['OpenGraph'][$params['key']] = $params['value'];
}
