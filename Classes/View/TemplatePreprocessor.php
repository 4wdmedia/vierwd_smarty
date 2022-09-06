<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View;

class TemplatePreprocessor {

	public function __invoke(string $template): string {
		$template = $this->replaceCacheBlocks($template);
		$template = $this->stripWhitespace($template);

		return $template;
	}

	protected function replaceCacheBlocks(string $template): string {
		$template = (string)preg_replace_callback('/{cacheCode cache_id=(?<cacheId>[^}]+)}(?<templateCode>.*?){\/cacheCode}/s', function(array $matches): string {
			$cacheId = $matches['cacheId'];
			$templateCode = $matches['templateCode'];

			$templateId = uniqid('template');
			$templateCode = sprintf('{capture assign=\'%s\'}{literal}%s{/literal}{/capture}{include \'string:\'|cat:$%s caching cache_id=%s}', $templateId, $templateCode, $templateId, $cacheId);

			return $templateCode;
		}, $template);

		return $template;
	}

	protected function stripWhitespace(string $template): string {
		$replacements = [
			'{typoscript' => '{/strip}{typoscript',
			'{/typoscript}' => '{/typoscript}{strip}',
			'{pre}' => '{/strip}',
			'{/pre}' => '{strip}',
		];

		// Smarty v3.1.32 changed handling of strip and comments.
		// Whitespace after comments is not stripped.
		// @see https://github.com/smarty-php/smarty/issues/436
		// We do not want this behaviour. That's why we strip all comments
		$template = (string)preg_replace('-\{\*.*?\*\}-s', '', $template);

		$search = array_keys($replacements);
		$replace = array_values($replacements);

		$template = str_replace($search, $replace, $template);
		$template = '{strip}' . $template . '{/strip}';
		return $template;
	}

}
