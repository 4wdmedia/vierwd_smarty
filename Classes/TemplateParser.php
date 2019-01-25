<?php

namespace Vierwd\VierwdSmarty;

use TYPO3\CMS\Fluid\Core\Parser\ParsingState;
use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\CMS\Fluid\Core\Parser\TemplateParser as FluidTemplateParser;
use Vierwd\VierwdSmarty\ViewHelper\SmartyViewHelper;

/**
 * Fake template parser, which delegates to Fluids template parser
 */
class TemplateParser extends FluidTemplateParser {
	public function parse($templateString) {
		if (substr($templateString, 0, 7) === 'smarty:') {
			$templateString = substr($templateString, 7);
			$state = $this->objectManager->get(ParsingState::class);
			$rootNode = $this->objectManager->get(RootNode::class);
			$state->setRootNode($rootNode);
			$state->pushNodeToStack($rootNode);

			$viewHelper = $this->objectManager->get(SmartyViewHelper::class);
			$node = $this->objectManager->get(ViewHelperNode::class, $viewHelper, []);
			$state->getNodeFromStack()->addChildNode($node);

			$textNode = $this->objectManager->get(TextNode::class, $templateString);
			$node->addChildNode($textNode);

			return $state;
		}

		return parent::parse($templateString);
	}
}
