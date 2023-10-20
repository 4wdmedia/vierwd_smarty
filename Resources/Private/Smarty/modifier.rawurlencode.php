<?php
declare(strict_types = 1);

function smarty_modifier_rawurlencode(?string $text): string {
	if (!is_string($text)) {
		return '';
	}

	return rawurlencode($text);
}
