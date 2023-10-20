<?php
declare(strict_types = 1);

function smarty_modifier_trim(?string $text): string {
	if (!is_string($text)) {
		return '';
	}

	return trim($text);
}
