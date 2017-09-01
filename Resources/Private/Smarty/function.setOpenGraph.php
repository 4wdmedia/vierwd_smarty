<?php

/**
 * set a global value which can be used in TypoScript with `data = global:OpenGraph|description`
 */
function smarty_function_setOpenGraph($params, $smarty) {
	if (!$GLOBALS['OpenGraph']) {
		$GLOBALS['OpenGraph'] = [];
	}

	$GLOBALS['OpenGraph'][$params['key']] = $params['value'];
}
