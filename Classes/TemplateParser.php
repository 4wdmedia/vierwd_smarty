<?php

namespace Vierwd\VierwdSmarty;

/**
 * Fake template parser, which delegates to Fluids template parser
 */
class TemplateParser extends \TYPO3\CMS\Fluid\Core\Parser\TemplateParser {

	public function parse($templateString) {
		if (substr($templateString, 0, 7) === 'smarty:') {
			$templateString = substr($templateString, 7);
			$state = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
			$rootNode = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class);
			$state->setRootNode($rootNode);
			$state->pushNodeToStack($rootNode);

			$viewHelper = $this->objectManager->get(\Vierwd\VierwdSmarty\ViewHelper\SmartyViewHelper::class);
			$node = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, $viewHelper, []);
			$state->getNodeFromStack()->addChildNode($node);

			$textNode = $this->objectManager->get(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode::class, $templateString);
			$node->addChildNode($textNode);

			return $state;
		}

		return parent::parse($templateString);
	}
}