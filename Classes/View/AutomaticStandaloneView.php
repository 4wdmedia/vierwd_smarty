<?php

namespace Vierwd\VierwdSmarty\View;

use TYPO3\CMS\Extbase\Utility\ArrayUtility;

/**
 * Standalone view which automatically renders a template using fluid or smarty depending on file extension
 */
class AutomaticStandaloneView extends \TYPO3\CMS\Fluid\View\StandaloneView {

	public function __construct(ContentObjectRenderer $contentObject = null) {
		parent::__construct($controllerContext);

		$this->templateParser = $this->objectManager->get(\Vierwd\VierwdSmarty\TemplateParser::class);
	}

	public function getTemplateRootPaths() {
		return $this->templateRootPaths;
	}

	public function buildListOfTemplateCandidates($templateName, array $paths, $format) {
		$possibleTemplatePaths = array();
		$paths = ArrayUtility::sortArrayWithIntegerKeys($paths);
		$paths = array_reverse($paths, true);
		foreach ($paths as $layoutRootPath) {
			$possibleTemplatePaths[] = $this->resolveFileNamePath($layoutRootPath . '/' . $templateName . '.tpl');
		}

		$possibleTemplatePaths = array_merge($possibleTemplatePaths, parent::buildListOfTemplateCandidates($templateName, $paths, $format));
		return $possibleTemplatePaths;
	}

	protected function getLayoutSource($layoutName = 'Default') {
		$layoutPathAndFilename = $this->getLayoutPathAndFilename($layoutName);
		$layoutSource = file_get_contents($layoutPathAndFilename);
		if ($layoutSource === false) {
			throw new InvalidTemplateResourceException('"' . $layoutPathAndFilename . '" is not a valid template resource URI.', 1312215888);
		}

		if (substr($layoutPathAndFilename, -4) === '.tpl') {
			return 'smarty:' . $layoutPathAndFilename;
		}
		return $layoutSource;
	}

	protected function getTemplateSource($actionName = null) {
		if ($this->templateSource === null && $this->templatePathAndFilename === null) {
			throw new InvalidTemplateResourceException('No template has been specified. Use either setTemplateSource() or setTemplatePathAndFilename().', 1288085266);
		}
		if ($this->templateSource === null) {
			if (!$this->testFileExistence($this->templatePathAndFilename)) {
				throw new InvalidTemplateResourceException('Template could not be found at "' . $this->templatePathAndFilename . '".', 1288087061);
			}
			$this->templateSource = file_get_contents($this->templatePathAndFilename);
		}

		if (substr($this->templatePathAndFilename, -4) === '.tpl') {
			return 'smarty:' . $this->templatePathAndFilename;
		}
		return $this->templateSource;
	}

	protected function getPartialSource($partialName) {
		$partialPathAndFilename = $this->getPartialPathAndFilename($partialName);
		$partialSource = file_get_contents($partialPathAndFilename);
		if ($partialSource === false) {
			throw new InvalidTemplateResourceException('"' . $partialPathAndFilename . '" is not a valid template resource URI.', 1257246932);
		}

		if (substr($partialPathAndFilename, -4) === '.tpl') {
			return 'smarty:' . $partialPathAndFilename;
		}
		return $partialSource;
	}

	public function renderSection($sectionName, array $variables, $ignoreUnknown = false) {
		if (isset(SmartyView::$sections, SmartyView::$sections[$sectionName])) {
			$section = SmartyView::$sections[$sectionName];
			unset(SmartyView::$sections[$sectionName]);
			return $section;
		}

		return parent::renderSection($sectionName, $variables, $ignoreUnknown);
	}

	public function renderLayout($layoutName, $parsedTemplate = null) {
		if (!$parsedTemplate) {
			$parsedTemplate = $this->templateParser->parse('');
		}

		$this->startRendering(self::RENDERING_LAYOUT, $parsedTemplate, $this->baseRenderingContext);
		$parsedLayout = $this->templateParser->parse($this->getLayoutSource($layoutName));
		$output = $parsedLayout->render($this->baseRenderingContext);
		$this->stopRendering();
		return $output;
	}

	protected function getCurrentRenderingContext() {
		$context = parent::getCurrentRenderingContext();
		if (!$context) {
			return $this->baseRenderingContext;
		}

		return $context;
	}
}
