<?php
declare(strict_types = 1);

function smarty_modifier_nl2p(string $content): string {
	$content = trim($content);
	if (!$content) {
		return '';
	}

	return '<p>' . nl2br((string)preg_replace('/\n{2,}/', '</p><p>', str_replace("\r", '', $content))) . '</p>';
}
